<?php

declare(strict_types=1);

namespace Tests\Feature\Study;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class FlashcardDeckV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    // ---------------------------------------------------------------- fixtures

    private function createCatalogue(SavedListType $type, bool $public = true, ?User $owner = null): Catalogue
    {
        return Catalogue::factory()
            ->byUser($owner ?? User::factory()->create())
            ->ofType($type)
            ->create(['publicity' => $public]);
    }

    private function attach(Catalogue $catalogue, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'real_object_id' => $itemId,
            'listtype_id' => $catalogue->type->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertKanji(string $kanji, string $onyomi, string $kunyomi, string $meaning, string $jlpt = '5'): int
    {
        return (int) DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => $onyomi,
            'kunyomi' => $kunyomi,
            'meaning' => $meaning,
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => 8,
            'jlpt' => $jlpt,
            'frequency' => '100',
            'radicals' => '-',
            'radical_parts' => '-',
        ]);
    }

    /**
     * @param list<string> $glosses
     */
    private function insertWord(string $word, string $furigana, array $glosses, string $jlpt = '-'): int
    {
        return (int) DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1000000',
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => $jlpt,
            'word_type' => 'noun (common) (futsuumeishi)|',
            'word_k_ele' => '[]',
            'furigana_r_ele' => '[]',
            'sense' => json_encode([[['gloss', $glosses]]], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function insertRadical(string $radical, string $meaning, string $hiragana, int $strokes = 1): int
    {
        return (int) DB::table('japanese_radicals_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'radical' => $radical,
            'strokes' => $strokes,
            'meaning' => $meaning,
            'hiragana' => $hiragana,
        ]);
    }

    private function deckUrl(Catalogue $catalogue, array $query = []): string
    {
        $url = "/api/v1/catalogues/{$catalogue->uuid}/flashcards";

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    // ------------------------------------------------------------------- kanji

    public function test_kanji_deck_carries_meanings_onyomi_and_kunyomi_as_accepted_answers(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $id = $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|rank next|come after|-ous|', '1');
        $this->attach($catalogue, $id);

        $meaning = $this->getJson($this->deckUrl($catalogue, ['prompt' => 'character', 'answer' => 'meaning', 'seed' => 7]));
        $meaning->assertOk()
            ->assertJsonPath('catalogue.uuid', $catalogue->uuid)
            ->assertJsonPath('catalogue.type', SavedListType::KANJIS->value)
            ->assertJsonPath('config.prompt', 'character')
            ->assertJsonPath('config.answer', 'meaning')
            ->assertJsonPath('config.mode', 'options')
            ->assertJsonPath('config.script', 'strict')
            ->assertJsonPath('config.count', 20)
            ->assertJsonPath('config.seed', 7)
            ->assertJsonPath('cards.0.item_id', $id)
            ->assertJsonPath('cards.0.prompt.text', '亜')
            ->assertJsonPath('cards.0.accepted_answers', ['Asia', 'rank next', 'come after', '-ous'])
            ->assertJsonPath('cards.0.display_answer', 'Asia')
            ->assertJsonPath('cards.0.options.0', 'Asia')
            ->assertJsonPath('cards.0.meta.jlpt', '1')
            ->assertJsonPath('cards.0.meta.grade', '1')
            ->assertJsonPath('cards.0.meta.strokes', 8)
            ->assertJsonPath('total_items', 1)
            ->assertJsonPath('eligible_items', 1)
            ->assertJsonPath('excluded.empty_answer_field', 0);

        $this->assertSame(
            ['catalogue', 'config', 'cards', 'total_items', 'eligible_items', 'excluded'],
            array_keys($meaning->json()),
        );

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'onyomi', 'mode' => 'typed']))
            ->assertOk()
            ->assertJsonPath('cards.0.accepted_answers', ['ア']);

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'kunyomi', 'mode' => 'typed']))
            ->assertOk()
            ->assertJsonPath('cards.0.accepted_answers', ['つ.ぐ']);
    }

    public function test_kanji_with_no_kunyomi_is_excluded_and_counted(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|'));
        $this->attach($catalogue, $this->insertKanji('唖', 'ア|アク|', '-', 'mute|dumb|'));

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'kunyomi']))
            ->assertOk()
            ->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.prompt.text', '亜')
            ->assertJsonPath('total_items', 2)
            ->assertJsonPath('eligible_items', 1)
            ->assertJsonPath('excluded.empty_answer_field', 1);
    }

    public function test_meaning_prompt_asks_for_the_character(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('学', 'ガク|', 'まな.ぶ|', 'study|learning|science|'));

        $this->getJson($this->deckUrl($catalogue, ['prompt' => 'meaning', 'answer' => 'character']))
            ->assertOk()
            ->assertJsonPath('cards.0.prompt.text', 'study, learning, science')
            ->assertJsonPath('cards.0.accepted_answers', ['学'])
            ->assertJsonPath('cards.0.display_answer', '学');
    }

    // ------------------------------------------------------------------- words

    public function test_word_deck_reads_furigana_and_glosses(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::WORDS);
        $id = $this->insertWord('学校', 'がっこう', ['school', 'academy'], '5');
        $this->attach($catalogue, $id);

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'reading', 'mode' => 'typed']))
            ->assertOk()
            ->assertJsonPath('cards.0.prompt.text', '学校')
            ->assertJsonPath('cards.0.prompt.hint', 'noun (common) (futsuumeishi)')
            ->assertJsonPath('cards.0.accepted_answers', ['がっこう'])
            ->assertJsonPath('cards.0.meta.jlpt', '5')
            ->assertJsonPath('cards.0.meta.strokes', null);

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'meaning']))
            ->assertOk()
            ->assertJsonPath('cards.0.accepted_answers', ['school', 'academy']);
    }

    public function test_kana_only_word_is_excluded_from_a_reading_deck(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KNOWNWORDS);
        $this->attach($catalogue, $this->insertWord('おなじ', 'おなじ', ['same']));
        $this->attach($catalogue, $this->insertWord('学校', 'がっこう', ['school']));

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'reading']))
            ->assertOk()
            ->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.prompt.text', '学校')
            ->assertJsonPath('excluded.empty_answer_field', 1);

        // The same kana-only word is a fine meaning card.
        $this->getJson($this->deckUrl($catalogue, ['answer' => 'meaning']))
            ->assertOk()
            ->assertJsonCount(2, 'cards')
            ->assertJsonPath('excluded.empty_answer_field', 0);
    }

    // ---------------------------------------------------------------- radicals

    public function test_radical_reading_accepts_both_kana_and_romaji_halves(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::RADICALS);
        $this->attach($catalogue, $this->insertRadical('一', 'one', 'いち / ichi', 1));

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'reading', 'mode' => 'typed']))
            ->assertOk()
            ->assertJsonPath('cards.0.prompt.text', '一')
            ->assertJsonPath('cards.0.accepted_answers', ['いち', 'ichi'])
            ->assertJsonPath('cards.0.display_answer', 'いち')
            ->assertJsonPath('cards.0.meta.strokes', 1)
            ->assertJsonPath('cards.0.meta.jlpt', null);

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'meaning']))
            ->assertOk()
            ->assertJsonPath('cards.0.accepted_answers', ['one']);
    }

    // ---------------------------------------------------------------- failures

    public function test_all_items_excluded_is_a_typed_422(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('唖', 'ア|', '-', 'mute|'));

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'kunyomi']))
            ->assertStatus(422)
            ->assertJsonPath('title', 'No eligible cards');
    }

    public function test_empty_catalogue_is_a_typed_422(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);

        $this->getJson($this->deckUrl($catalogue))
            ->assertStatus(422)
            ->assertJsonPath('title', 'No eligible cards');
    }

    public function test_sentences_catalogue_is_not_supported(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::SENTENCES);

        $this->getJson($this->deckUrl($catalogue))
            ->assertStatus(422)
            ->assertJsonPath('title', 'Catalogue type not supported for study');
    }

    public function test_field_combination_invalid_for_the_type_is_a_typed_422(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|'));

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'reading']))
            ->assertStatus(422)
            ->assertJsonPath('title', 'Invalid flashcard configuration');
    }

    public function test_request_level_validation_rejects_same_fields_and_typed_character(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);

        $this->getJson($this->deckUrl($catalogue, ['prompt' => 'meaning', 'answer' => 'meaning']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answer']);

        $this->getJson($this->deckUrl($catalogue, ['prompt' => 'meaning', 'answer' => 'character', 'mode' => 'typed']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mode']);

        $this->getJson($this->deckUrl($catalogue, ['answer' => 'nonsense']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answer']);

        $this->getJson($this->deckUrl($catalogue, ['count' => 101]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['count']);
    }

    // -------------------------------------------------------------- visibility

    private function privateCatalogueWithOneKanji(User $owner): Catalogue
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS, public: false, owner: $owner);
        $this->attach($catalogue, $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|'));

        return $catalogue;
    }

    public function test_private_catalogue_forbids_anonymous_viewer(): void
    {
        $catalogue = $this->privateCatalogueWithOneKanji(User::factory()->create());

        $this->getJson($this->deckUrl($catalogue))->assertStatus(403);
    }

    public function test_private_catalogue_forbids_other_user(): void
    {
        $catalogue = $this->privateCatalogueWithOneKanji(User::factory()->create());

        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->getJson($this->deckUrl($catalogue))->assertStatus(403);
    }

    public function test_private_catalogue_allows_owner(): void
    {
        $owner = User::factory()->create();
        $catalogue = $this->privateCatalogueWithOneKanji($owner);

        Passport::actingAs($owner, ['*'], 'api');

        $this->getJson($this->deckUrl($catalogue))->assertOk();
    }

    public function test_private_catalogue_allows_admin(): void
    {
        $catalogue = $this->privateCatalogueWithOneKanji(User::factory()->create());

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin, ['*'], 'api');

        $this->getJson($this->deckUrl($catalogue))->assertOk();
    }

    public function test_unknown_catalogue_is_404(): void
    {
        $this->getJson('/api/v1/catalogues/'.Str::uuid().'/flashcards')->assertStatus(404);
        $this->getJson('/api/v1/catalogues/not-a-uuid/flashcards')->assertStatus(404);
    }

    public function test_deck_read_does_not_record_a_view(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|'));

        $this->getJson($this->deckUrl($catalogue))->assertOk();

        $this->assertSame(0, DB::table('views')->count());
    }

    // ----------------------------------------------------------- shuffle, cut

    public function test_same_seed_reproduces_the_deck_and_count_cuts_it(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        foreach (['一', '二', '三', '四', '五', '六'] as $i => $glyph) {
            $this->attach($catalogue, $this->insertKanji($glyph, 'オン|', 'くん|', "number {$i}|"));
        }

        $first = $this->getJson($this->deckUrl($catalogue, ['seed' => 42]))->assertOk()->json('cards.*.item_id');
        $second = $this->getJson($this->deckUrl($catalogue, ['seed' => 42]))->assertOk()->json('cards.*.item_id');
        $other = $this->getJson($this->deckUrl($catalogue, ['seed' => 43]))->assertOk()->json('cards.*.item_id');

        $this->assertCount(6, $first);
        $this->assertSame($first, $second);
        $this->assertEqualsCanonicalizing($first, $other);
        $this->assertNotSame($first, $other, 'different seeds should give a different order');

        $this->getJson($this->deckUrl($catalogue, ['seed' => 42, 'count' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'cards')
            ->assertJsonPath('cards.*.item_id', array_slice($first, 0, 2))
            ->assertJsonPath('total_items', 6)
            ->assertJsonPath('eligible_items', 6);
    }

    public function test_omitted_seed_is_generated_and_returned(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|'));

        $seed = $this->getJson($this->deckUrl($catalogue))->assertOk()->json('config.seed');

        $this->assertIsInt($seed);
        $this->assertGreaterThanOrEqual(0, $seed);
    }

    public function test_query_count_does_not_grow_with_the_number_of_items(): void
    {
        $small = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($small, $this->insertKanji('一', 'イチ|', 'ひと|', 'one|'));

        $large = $this->createCatalogue(SavedListType::KANJIS);
        foreach (['一', '二', '三', '四', '五', '六', '七', '八'] as $glyph) {
            $this->attach($large, $this->insertKanji($glyph, 'オン|', 'くん|', 'number|'));
        }

        DB::enableQueryLog();
        $this->getJson($this->deckUrl($small))->assertOk();
        $smallQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $this->getJson($this->deckUrl($large))->assertOk();
        $largeQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($smallQueries, $largeQueries);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Relation filters on the dictionary indexes (#352): the words and sentences written with one
 * kanji, read page by page in the index's own shape for the dictionary detail pages. Dictionary
 * data is public, so an unknown uuid is an empty page rather than a 404.
 */
class DictionaryRelationIndexFilterTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();
    }

    public function test_the_word_index_lists_only_the_words_written_with_the_kanji(): void
    {
        $water = $this->createKanji('水');
        $fire = $this->createKanji('火');
        $this->linkWord($this->createWord('水道'), $water['id']);
        $this->linkWord($this->createWord('水泳'), $water['id']);
        $this->linkWord($this->createWord('火山'), $fire['id']);

        $this->getJson("/api/v1/words?kanji_uuid={$water['uuid']}")
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('items.0.word', '水道')
            ->assertJsonPath('items.1.word', '水泳');
    }

    public function test_the_word_index_pages_through_a_kanji(): void
    {
        $water = $this->createKanji('水');

        foreach (['水道', '水泳', '水曜日'] as $word) {
            $this->linkWord($this->createWord($word), $water['id']);
        }

        $this->getJson("/api/v1/words?kanji_uuid={$water['uuid']}&per_page=2&page=2")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.word', '水曜日')
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2);
    }

    public function test_the_word_filter_combines_with_the_keyword_and_the_viewer_catalogue_state(): void
    {
        $water = $this->createKanji('水');
        $waterworks = $this->createWord('水道', 'waterworks');
        $this->linkWord($waterworks, $water['id']);
        $this->linkWord($this->createWord('水泳', 'swimming'), $water['id']);

        $owner = User::factory()->create();
        $catalogue = $this->createCatalogue($owner, SavedListType::WORDS);
        $this->save($catalogue, $waterworks);
        Passport::actingAs($owner);

        $this->getJson("/api/v1/words?kanji_uuid={$water['uuid']}&keyword=waterworks&include=viewer_catalogue_state")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.word', '水道')
            ->assertJsonPath('items.0.viewer_catalogue_state.is_saved', true);
    }

    public function test_the_sentence_index_lists_only_the_sentences_written_with_the_kanji(): void
    {
        $water = $this->createKanji('水');
        $fire = $this->createKanji('火');
        $this->linkSentence($this->createSentence('水を飲む。'), $water['id']);
        $this->linkSentence($this->createSentence('水が冷たい。'), $water['id']);
        $this->linkSentence($this->createSentence('火が強い。'), $fire['id']);

        $this->getJson("/api/v1/sentences?kanji_uuid={$water['uuid']}&per_page=1")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.content', '水を飲む。')
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_the_sentence_filter_combines_with_the_keyword(): void
    {
        $water = $this->createKanji('水');
        $this->linkSentence($this->createSentence('水を飲む。'), $water['id']);
        $this->linkSentence($this->createSentence('水が冷たい。'), $water['id']);

        $this->getJson("/api/v1/sentences?kanji_uuid={$water['uuid']}&keyword=".urlencode('冷たい'))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.content', '水が冷たい。');
    }

    public function test_an_unknown_kanji_gives_an_empty_page_and_a_malformed_one_a_422(): void
    {
        $water = $this->createKanji('水');
        $this->linkWord($this->createWord('水道'), $water['id']);
        $this->linkSentence($this->createSentence('水を飲む。'), $water['id']);
        $unknown = (string) Str::uuid();

        foreach (['words', 'sentences'] as $index) {
            $this->getJson("/api/v1/{$index}?kanji_uuid={$unknown}")
                ->assertOk()
                ->assertJsonCount(0, 'items')
                ->assertJsonPath('pagination.total', 0);

            $this->getJson("/api/v1/{$index}?kanji_uuid=12")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('kanji_uuid');
        }
    }

    /**
     * @return array{id: int, uuid: string}
     */
    private function createKanji(string $kanji): array
    {
        $uuid = (string) Str::uuid();
        $id = DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => $uuid,
            'kanji' => $kanji,
            'onyomi' => '-',
            'kunyomi' => '-',
            'meaning' => '-',
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => '-',
            'radical_parts' => '-',
        ]);

        return ['id' => $id, 'uuid' => $uuid];
    }

    private function createWord(string $word, string $gloss = '-'): int
    {
        return DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1001',
            'word' => $word,
            'furigana' => '-',
            'jlpt' => '-',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => '-',
            'sense' => $gloss,
        ]);
    }

    private function createSentence(string $content): int
    {
        return DB::table('japanese_tatoeba_sentences')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'content' => $content,
            'tatoeba_entry' => (string) random_int(1, 999999),
        ]);
    }

    private function linkWord(int $wordId, int $kanjiId): void
    {
        DB::table('japanese_kanji_word_long')->insert(['kanji_id' => $kanjiId, 'word_id' => $wordId]);
    }

    private function linkSentence(int $sentenceId, int $kanjiId): void
    {
        DB::table('japanese_sentence_kanji')->insert(['kanji_id' => $kanjiId, 'sentence_id' => $sentenceId]);
    }

    private function createCatalogue(User $owner, SavedListType $type): Catalogue
    {
        return Catalogue::create([
            'user_id' => $owner->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'title' => $type->label().' catalogue',
            'description' => '',
            'publicity' => 1,
            'type' => $type->value,
        ]);
    }

    private function save(Catalogue $catalogue, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'listtype_id' => $catalogue->type->value,
            'real_object_id' => $itemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

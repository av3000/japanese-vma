<?php

declare(strict_types=1);

namespace Tests\Feature\Study;

use App\Application\Study\Interfaces\Readers\DistractorPoolReaderInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class FlashcardDistractorsV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    // ---------------------------------------------------------------- fixtures

    private function createCatalogue(SavedListType $type): Catalogue
    {
        return Catalogue::factory()
            ->byUser(User::factory()->create())
            ->ofType($type)
            ->create(['publicity' => true]);
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

    private function insertKanji(string $kanji, string $meaning, string $jlpt = '5', string $kunyomi = 'くん|'): int
    {
        return (int) DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'オン|',
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

    private function insertRadical(string $radical, string $meaning, int $strokes): int
    {
        return (int) DB::table('japanese_radicals_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'radical' => $radical,
            'strokes' => $strokes,
            'meaning' => $meaning,
            'hiragana' => 'よみ / yomi',
        ]);
    }

    /**
     * @param list<string> $glosses
     */
    private function insertWord(string $word, string $furigana, array $glosses): int
    {
        return (int) DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1000000',
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => '-',
            'word_type' => 'noun|',
            'word_k_ele' => '[]',
            'furigana_r_ele' => '[]',
            'sense' => json_encode([[['gloss', $glosses]]], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function deckUrl(Catalogue $catalogue, array $query = []): string
    {
        // Caller's params win; the defaults only fill what the caller left out.
        return "/api/v1/catalogues/{$catalogue->uuid}/flashcards?".http_build_query($query + ['mode' => 'options', 'seed' => 11]);
    }

    /**
     * @return list<array{display_answer: string, options: list<string>}>
     */
    private function cards(Catalogue $catalogue, array $query = []): array
    {
        return $this->getJson($this->deckUrl($catalogue, $query))->assertOk()->json('cards');
    }

    private function assertFourDistinctOptionsIncludingTheAnswer(array $card): void
    {
        $this->assertCount(4, $card['options'], $card['display_answer']);
        $this->assertCount(4, array_unique(array_map('mb_strtolower', $card['options'])), 'options must be distinct');
        $this->assertContains($card['display_answer'], $card['options']);
    }

    // ------------------------------------------------------------------- tests

    public function test_a_deck_with_enough_distinct_cards_supplies_its_own_distractors(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        foreach (['一' => 'one|', '二' => 'two|', '三' => 'three|', '四' => 'four|', '五' => 'five|'] as $glyph => $meaning) {
            $this->attach($catalogue, $this->insertKanji($glyph, $meaning));
        }
        // Dictionary noise the deck must not need.
        $this->insertKanji('学', 'study|');

        $cards = $this->cards($catalogue);
        $deckAnswers = array_column($cards, 'display_answer');

        $this->assertCount(5, $cards);

        foreach ($cards as $card) {
            $this->assertFourDistinctOptionsIncludingTheAnswer($card);

            foreach ($card['options'] as $option) {
                $this->assertContains($option, $deckAnswers, 'every option comes from the deck');
            }
        }
    }

    public function test_a_one_card_deck_fills_from_the_pool_preferring_the_same_jlpt_level(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('一', 'one|', '5'));

        foreach (['二' => 'two|', '三' => 'three|', '四' => 'four|', '五' => 'five|'] as $glyph => $meaning) {
            $this->insertKanji($glyph, $meaning, '5');
        }
        foreach (['亜' => 'Asia|', '唖' => 'mute|', '娃' => 'beautiful|'] as $glyph => $meaning) {
            $this->insertKanji($glyph, $meaning, '1');
        }

        [$card] = $this->cards($catalogue);

        $this->assertFourDistinctOptionsIncludingTheAnswer($card);

        $distractors = array_values(array_diff($card['options'], ['one']));
        foreach ($distractors as $distractor) {
            $this->assertContains($distractor, ['two', 'three', 'four', 'five'], 'N5 pool preferred over N1');
        }
    }

    public function test_the_pool_falls_back_to_other_levels_when_the_preferred_one_is_short(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('一', 'one|', '5'));

        $this->insertKanji('二', 'two|', '5');
        $this->insertKanji('亜', 'Asia|', '1');
        $this->insertKanji('唖', 'mute|', '2');

        [$card] = $this->cards($catalogue);

        $this->assertFourDistinctOptionsIncludingTheAnswer($card);
        $this->assertEqualsCanonicalizing(['one', 'two', 'Asia', 'mute'], $card['options']);
    }

    public function test_cards_sharing_a_gloss_are_never_each_others_distractor(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('学', 'study|learn|'));
        $this->attach($catalogue, $this->insertKanji('習', 'learn|practice|'));
        $this->attach($catalogue, $this->insertKanji('一', 'one|'));
        $this->attach($catalogue, $this->insertKanji('二', 'two|'));

        $this->insertKanji('三', 'three|');
        $this->insertKanji('四', 'four|');

        $byAnswer = [];
        foreach ($this->cards($catalogue) as $card) {
            $byAnswer[$card['display_answer']] = $card['options'];
        }

        $this->assertNotContains('learn', $byAnswer['study'], '習ʼs display answer shares "learn" with 学');
        $this->assertNotContains('practice', $byAnswer['study']);
        $this->assertNotContains('study', $byAnswer['learn'], '学ʼs display answer shares "learn" with 習');
        $this->assertCount(4, $byAnswer['study']);
        $this->assertCount(4, $byAnswer['learn']);
    }

    public function test_options_are_deduplicated_after_normalization(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('二', 'two|'));

        $this->insertKanji('一', 'One|');
        $this->insertKanji('壱', 'one|');
        $this->insertKanji('三', 'three|');
        $this->insertKanji('四', 'four|');

        [$card] = $this->cards($catalogue);

        $this->assertFourDistinctOptionsIncludingTheAnswer($card);
        $this->assertCount(1, array_intersect($card['options'], ['One', 'one']), 'One and one are the same option');
    }

    public function test_the_same_seed_gives_the_same_option_order(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        foreach (['一' => 'one|', '二' => 'two|', '三' => 'three|', '四' => 'four|', '五' => 'five|', '六' => 'six|'] as $glyph => $meaning) {
            $this->attach($catalogue, $this->insertKanji($glyph, $meaning));
        }

        $first = array_column($this->cards($catalogue, ['seed' => 99]), 'options');
        $second = array_column($this->cards($catalogue, ['seed' => 99]), 'options');
        $other = array_column($this->cards($catalogue, ['seed' => 100]), 'options');

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $other);
    }

    public function test_pool_distractors_are_reproducible_for_the_same_seed(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('一', 'one|'));

        foreach (['二' => 'two|', '三' => 'three|', '四' => 'four|', '五' => 'five|', '六' => 'six|', '七' => 'seven|', '八' => 'eight|', '九' => 'nine|'] as $glyph => $meaning) {
            $this->insertKanji($glyph, $meaning);
        }

        [$first] = $this->cards($catalogue, ['seed' => 5]);
        [$second] = $this->cards($catalogue, ['seed' => 5]);

        $this->assertFourDistinctOptionsIncludingTheAnswer($first);
        $this->assertSame($first['options'], $second['options'], 'pool distractors must follow the seed too');
    }

    public function test_typed_mode_has_no_options(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->insertKanji('一', 'one|'));

        $this->getJson($this->deckUrl($catalogue, ['mode' => 'typed']))
            ->assertOk()
            ->assertJsonPath('cards.0.options', null);
    }

    public function test_radical_pool_prefers_the_same_stroke_count(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::RADICALS);
        $this->attach($catalogue, $this->insertRadical('一', 'one', 1));

        foreach (['丨' => 'line', '丶' => 'dot', '丿' => 'slash', '乙' => 'second'] as $glyph => $meaning) {
            $this->insertRadical($glyph, $meaning, 1);
        }
        $this->insertRadical('人', 'person', 2);
        $this->insertRadical('刀', 'sword', 2);

        [$card] = $this->cards($catalogue);

        $this->assertFourDistinctOptionsIncludingTheAnswer($card);
        foreach (array_diff($card['options'], ['one']) as $distractor) {
            $this->assertContains($distractor, ['line', 'dot', 'slash', 'second']);
        }
    }

    public function test_word_pool_skips_kana_only_words_for_reading_decks(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::WORDS);
        $this->attach($catalogue, $this->insertWord('学校', 'がっこう', ['school']));

        $this->insertWord('おなじ', 'おなじ', ['same']);
        $this->insertWord('先生', 'せんせい', ['teacher']);
        $this->insertWord('学生', 'がくせい', ['student']);
        $this->insertWord('病院', 'びょういん', ['hospital']);

        [$card] = $this->cards($catalogue, ['answer' => 'reading']);

        $this->assertFourDistinctOptionsIncludingTheAnswer($card);
        $this->assertEqualsCanonicalizing(['がっこう', 'せんせい', 'がくせい', 'びょういん'], $card['options']);
    }

    public function test_pool_reader_excludes_every_catalogue_item_not_only_the_cut_deck(): void
    {
        $excluded = [$this->insertKanji('一', 'one|'), $this->insertKanji('二', 'two|')];
        $this->insertKanji('三', 'three|');

        $config = new FlashcardConfig(FlashcardField::CHARACTER, FlashcardField::MEANING, AnswerMode::OPTIONS, ScriptStrictness::STRICT, 20, 1);

        $pool = $this->app->make(DistractorPoolReaderInterface::class)
            ->sample(SavedListType::KANJIS, $config, $excluded, null, null, 10);

        $this->assertSame(['three'], array_map(static fn (Flashcard $card): string => $card->displayAnswer, $pool));
    }

    public function test_query_count_is_bounded_by_levels_not_by_deck_size(): void
    {
        $small = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($small, $this->insertKanji('一', 'number|', '5'));

        $large = $this->createCatalogue(SavedListType::KANJIS);
        foreach (['二', '三', '四', '五', '六', '七', '八', '九'] as $glyph) {
            // Every card shares the gloss, so none can be another's distractor: all need the pool.
            $this->attach($large, $this->insertKanji($glyph, 'number|', '5'));
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

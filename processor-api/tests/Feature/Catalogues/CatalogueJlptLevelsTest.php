<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Catalogue `jlpt_levels` (#386): counted from the catalogue's items by catalogue type.
 */
class CatalogueJlptLevelsTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const ZERO = ['n1' => 0, 'n2' => 0, 'n3' => 0, 'n4' => 0, 'n5' => 0, 'uncommon' => 0];

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();
        $this->owner = User::factory()->create();
    }

    public function test_a_kanji_catalogue_counts_its_kanji_by_level(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $water = $this->createKanji('水', '5');
        $this->attach($catalogue, $water);
        // Saved twice, counted once.
        $this->attach($catalogue, $water);
        $this->attach($catalogue, $this->createKanji('火', '5'));
        $this->attach($catalogue, $this->createKanji('育', '3'));
        $this->attach($catalogue, $this->createKanji('鬱', '-'));

        $this->assertListedLevels($catalogue, ['n3' => 1, 'n5' => 2, 'uncommon' => 1]);
    }

    public function test_a_known_kanji_catalogue_counts_like_a_kanji_catalogue(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KNOWNKANJIS, ['publicity' => 0]);
        $this->attach($catalogue, $this->createKanji('水', '5'));
        Passport::actingAs($this->owner);

        $this->getJson('/api/v1/catalogues?include_jlpt_levels=true&custom_only=false&public_only=false&owner_uid='.$this->owner->uuid)
            ->assertOk()
            ->assertJsonPath('items.0.uuid', $catalogue->uuid)
            ->assertJsonPath('items.0.jlpt_levels', $this->levels(['n5' => 1]));
    }

    public function test_a_words_catalogue_counts_its_words_by_level(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::WORDS);
        $this->attach($catalogue, $this->createWord('勉強', '5'));
        // The word bank's level format is not settled; an N prefix still counts.
        $this->attach($catalogue, $this->createWord('経済', 'N3'));
        $this->attach($catalogue, $this->createWord('曖昧', '-'));

        $this->assertListedLevels($catalogue, ['n3' => 1, 'n5' => 1, 'uncommon' => 1]);
    }

    public function test_a_sentences_catalogue_counts_each_word_of_its_sentences_once(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::SENTENCES);
        $water = $this->createWord('水', '5');
        $drink = $this->createWord('飲む', '-');
        $first = $this->createSentence('水を飲みます。', [$water, $drink]);
        $second = $this->createSentence('水です。', [$water]);
        $this->attach($catalogue, $first);
        $this->attach($catalogue, $second);

        $this->assertListedLevels($catalogue, ['n5' => 1, 'uncommon' => 1]);
    }

    public function test_an_articles_catalogue_counts_the_distinct_kanji_across_its_articles(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::ARTICLES);
        $water = $this->createKanji('水', '5');
        $fire = $this->createKanji('火', '1');
        // Each article alone has n5 1; summing their jlpt_levels would say n5 2.
        $this->attach($catalogue, $this->createArticle([$water, $fire]));
        $this->attach($catalogue, $this->createArticle([$water]));

        $this->assertListedLevels($catalogue, ['n1' => 1, 'n5' => 1]);
    }

    public function test_a_radicals_catalogue_has_no_jlpt_levels(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::RADICALS);
        $this->attach($catalogue, 1);

        $this->getJson('/api/v1/catalogues?include_jlpt_levels=true')
            ->assertOk()
            ->assertJsonPath('items.0.uuid', $catalogue->uuid)
            ->assertJsonPath('items.0.jlpt_levels', null);
    }

    public function test_an_empty_catalogue_counts_zero_at_every_level(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);

        $this->assertListedLevels($catalogue, []);
    }

    public function test_a_level_outside_n1_to_n5_counts_as_uncommon(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->createKanji('〇', '0'));
        $this->attach($catalogue, $this->createKanji('々', '9'));
        $this->attach($catalogue, $this->createKanji('乂', ''));

        $this->assertListedLevels($catalogue, ['uncommon' => 3]);
    }

    public function test_the_list_leaves_jlpt_levels_out_unless_asked(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->createKanji('水', '5'));

        $this->getJson('/api/v1/catalogues')
            ->assertOk()
            ->assertJsonPath('items.0.uuid', $catalogue->uuid)
            ->assertJsonPath('items.0.jlpt_levels', null);
    }

    public function test_the_list_rejects_a_non_boolean_include_jlpt_levels(): void
    {
        $this->getJson('/api/v1/catalogues?include_jlpt_levels=often')
            ->assertStatus(422);
    }

    public function test_the_detail_always_carries_jlpt_levels(): void
    {
        $kanji = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($kanji, $this->createKanji('水', '5'));
        $radicals = $this->createCatalogue(SavedListType::RADICALS);

        $this->getJson("/api/v1/catalogues/{$kanji->uuid}")
            ->assertOk()
            ->assertJsonPath('jlpt_levels', $this->levels(['n5' => 1]));

        $this->getJson("/api/v1/catalogues/{$radicals->uuid}")
            ->assertOk()
            ->assertJsonPath('jlpt_levels', null);
    }

    /**
     * One grouped query per catalogue type on the page, whatever the number of catalogues.
     */
    public function test_the_query_count_does_not_grow_with_the_page(): void
    {
        $kanji = $this->createKanji('水', '5');
        $word = $this->createWord('勉強', '5');
        $this->attach($this->createCatalogue(SavedListType::KANJIS), $kanji);
        $this->attach($this->createCatalogue(SavedListType::WORDS), $word);

        $small = $this->countQueriesForListing();

        for ($i = 0; $i < 8; $i++) {
            $this->attach($this->createCatalogue(SavedListType::KANJIS), $kanji);
            $this->attach($this->createCatalogue(SavedListType::WORDS), $word);
        }

        $large = $this->countQueriesForListing();

        $this->assertSame($small, $large, "Query count grew from {$small} to {$large}: JLPT counts are not batched");
    }

    /**
     * @param array<string, int> $expected levels that are not zero
     */
    private function assertListedLevels(Catalogue $catalogue, array $expected): void
    {
        $this->getJson('/api/v1/catalogues?include_jlpt_levels=true')
            ->assertOk()
            ->assertJsonPath('items.0.uuid', $catalogue->uuid)
            ->assertJsonPath('items.0.jlpt_levels', $this->levels($expected));
    }

    /**
     * @param array<string, int> $nonZero
     *
     * @return array<string, int>
     */
    private function levels(array $nonZero): array
    {
        return array_merge(self::ZERO, $nonZero);
    }

    private function countQueriesForListing(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/catalogues?include_jlpt_levels=true&per_page=50')->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function createCatalogue(SavedListType $type, array $overrides = []): Catalogue
    {
        return Catalogue::create(array_merge([
            'user_id' => $this->owner->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'title' => $type->label().' catalogue',
            'description' => '',
            'publicity' => 1,
            'type' => $type->value,
        ], $overrides));
    }

    private function attach(Catalogue $catalogue, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'listtype_id' => $catalogue->type->value,
            'real_object_id' => $itemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKanji(string $kanji, string $jlpt): int
    {
        return DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => '-',
            'kunyomi' => '-',
            'meaning' => '-',
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => $jlpt,
            'frequency' => '1',
            'radicals' => '-',
            'radical_parts' => $kanji,
        ]);
    }

    private function createWord(string $word, string $jlpt): int
    {
        return DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1001',
            'word' => $word,
            'furigana' => '-',
            'jlpt' => $jlpt,
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => '-',
            'sense' => '-',
        ]);
    }

    /**
     * @param int[] $wordIds
     */
    private function createSentence(string $content, array $wordIds): int
    {
        $sentenceId = DB::table('japanese_tatoeba_sentences')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'content' => $content,
            'tatoeba_entry' => (string) random_int(1, 999999),
        ]);

        foreach ($wordIds as $wordId) {
            DB::table('japanese_sentence_word')->insert(['sentence_id' => $sentenceId, 'word_id' => $wordId]);
        }

        return $sentenceId;
    }

    /**
     * @param int[] $kanjiIds
     */
    private function createArticle(array $kanjiIds): int
    {
        $article = PersistenceArticle::factory()->byUser($this->owner)->create([
            'publicity' => PublicityStatus::PUBLIC,
            'status' => ArticleStatus::PENDING,
        ]);

        foreach ($kanjiIds as $kanjiId) {
            DB::table('article_kanji')->insert(['article_id' => $article->id, 'kanji_id' => $kanjiId]);
        }

        return $article->id;
    }
}

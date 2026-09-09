<?php

namespace Tests\Feature\Articles;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class IndexArticleTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function createArticle(User $user, array $overrides = []): PersistenceArticle
    {
        return PersistenceArticle::factory()
            ->byUser($user)
            ->create(array_merge([
                'publicity' => PublicityStatus::PUBLIC,
                'status' => ArticleStatus::PENDING,
            ], $overrides));
    }

    private function attachKanji(PersistenceArticle $article, string $kanji = '水'): void
    {
        $kanjiId = DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'water',
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => 'water',
            'radical_parts' => $kanji,
        ]);

        DB::table('article_kanji')->insert([
            'article_id' => $article->id,
            'kanji_id' => $kanjiId,
        ]);
    }

    private function assertArticleTitles(array $items, array $expectedTitles): void
    {
        $actualTitles = array_column($items, 'title_jp');
        sort($actualTitles);
        sort($expectedTitles);

        $this->assertSame($expectedTitles, $actualTitles);
    }

    public function test_index_filters_articles_by_author_uid_for_authenticated_owner(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();

        $this->createArticle($owner, [
            'title_jp' => 'Owner Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($owner, [
            'title_jp' => 'Owner Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);
        $this->createArticle($otherUser, [
            'title_jp' => 'Other Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->json('GET', '/api/v1/articles', [
                'author_uid' => $owner->uuid,
            ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Owner Private', 'Owner Public']);
    }

    public function test_index_filters_other_authors_private_articles_for_authenticated_non_admin(): void
    {
        $viewer = $this->createUser();
        $author = $this->createUser();

        $this->createArticle($author, [
            'title_jp' => 'Author Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($author, [
            'title_jp' => 'Author Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->json('GET', '/api/v1/articles', [
                'author_uid' => $author->uuid,
            ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Author Public']);
    }

    public function test_index_filters_other_authors_private_articles_for_anonymous_user(): void
    {
        $author = $this->createUser();

        $this->createArticle($author, [
            'title_jp' => 'Anonymous Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($author, [
            'title_jp' => 'Anonymous Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        $response = $this->json('GET', '/api/v1/articles', [
            'author_uid' => $author->uuid,
        ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Anonymous Public']);
    }

    public function test_index_returns_all_matching_author_articles_for_admin(): void
    {
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        $author = $this->createUser();

        $this->createArticle($author, [
            'title_jp' => 'Admin Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($author, [
            'title_jp' => 'Admin Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        Passport::actingAs($admin, ['*'], 'api');

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->json('GET', '/api/v1/articles', [
                'author_uid' => $author->uuid,
            ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Admin Private', 'Admin Public']);
    }

    public function test_index_rejects_invalid_author_uid(): void
    {
        $response = $this->json('GET', '/api/v1/articles', [
            'author_uid' => 'not-a-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author_uid']);
    }

    /**
     * Characterization only. The Article list does not filter on moderation status today,
     * so every status is visible under the public scope. AFM-01 must not change this;
     * status filtering belongs to the separate moderation work.
     */
    public function test_index_returns_every_moderation_status_under_the_public_scope(): void
    {
        $author = $this->createUser();

        foreach (ArticleStatus::cases() as $status) {
            $this->createArticle($author, [
                'title_jp' => 'Status '.$status->value,
                'publicity' => PublicityStatus::PUBLIC,
                'status' => $status,
            ]);
        }

        $response = $this->json('GET', '/api/v1/articles');

        $response->assertStatus(200);
        $this->assertArticleTitles(
            $response->json('items'),
            array_map(static fn (ArticleStatus $s): string => 'Status '.$s->value, ArticleStatus::cases()),
        );
    }

    public function test_index_rejects_unknown_query_parameters(): void
    {
        $response = $this->json('GET', '/api/v1/articles', [
            'not_a_filter' => 'nope',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['not_a_filter']);
    }

    /**
     * Characterization. Pagination already produces field-level 422s through
     * ValueObjectValidationException; these lock the behavior in before AFM-02/AFM-03.
     */
    #[DataProvider('outOfRangePaginationProvider')]
    public function test_index_rejects_out_of_range_pagination(string $field, int $value): void
    {
        $response = $this->json('GET', '/api/v1/articles', [$field => $value]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([$field]);
    }

    public static function outOfRangePaginationProvider(): array
    {
        return [
            'page below minimum' => ['page', 0],
            'per_page below minimum' => ['per_page', 0],
            'per_page above maximum' => ['per_page', 101],
        ];
    }

    public function test_index_accepts_the_remaining_include_flags(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Included']);

        $response = $this->json('GET', '/api/v1/articles', [
            'include_words' => false,
            'include_hashtags' => false,
        ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Included']);
    }

    public function test_index_returns_attached_kanjis(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);
        $this->attachKanji($article);

        $this->getJson('/api/v1/articles')
            ->assertOk()
            ->assertJsonPath('items.0.kanjis.0.character', '水');
    }

    public function test_index_returns_enriched_article_list_payload(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author, [
            'title_jp' => 'Tagged Article',
            'publicity' => PublicityStatus::PUBLIC,
        ]);

        $hashtagId = DB::table('uniquehashtags')->insertGetId([
            'content' => '#grammar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hashtag_entity')->insert([
            'entity_id' => $article->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'hashtag_id' => $hashtagId,
            'user_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('last_operations')->insert([
            'processable_id' => $article->uuid,
            'processable_type' => 'article',
            'task_type' => 'kanji_extraction',
            'status' => LastOperationStatus::COMPLETED->value,
            'metadata' => json_encode(['source' => 'test'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->json('GET', '/api/v1/articles');

        $response->assertStatus(200)
            ->assertJsonPath('items.0.hashtags.0.content', '#grammar')
            ->assertJsonPath('items.0.engagement.stats.likes_count', 0)
            ->assertJsonPath('items.0.processing_status.type', 'kanji_extraction')
            ->assertJsonPath('items.0.processing_status.status', LastOperationStatus::COMPLETED->value);
    }

    public function test_index_suppresses_stats_when_include_stats_counts_is_false(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, [
            'title_jp' => 'No Stats Article',
            'publicity' => PublicityStatus::PUBLIC,
        ]);

        $response = $this->json('GET', '/api/v1/articles', [
            'include_stats_counts' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.engagement.stats', null);
    }
    // ------------------------------------------------- AFM-03 canonical contract

    public function test_index_filters_by_canonical_jlpt_levels(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Beginner', 'n5' => 4]);
        $this->createArticle($author, ['title_jp' => 'Advanced', 'n1' => 3]);

        $response = $this->json('GET', '/api/v1/articles', ['jlpt_levels' => ['n5']]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Beginner']);
    }

    public function test_index_rejects_an_unknown_jlpt_level(): void
    {
        $this->json('GET', '/api/v1/articles', ['jlpt_levels' => ['n9']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jlpt_levels.0']);
    }

    public function test_index_accepts_canonical_q(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Findable', 'title_en' => 'Grammar Notes']);
        $this->createArticle($author, ['title_jp' => 'Hidden', 'title_en' => 'Something Else']);

        $response = $this->json('GET', '/api/v1/articles', ['q' => 'grammar']);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Findable']);
    }

    public function test_index_rejects_views_total_as_canonical_sort(): void
    {
        $this->json('GET', '/api/v1/articles', ['sort' => 'views_total'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_index_sorts_by_the_canonical_signed_key(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Older', 'created_at' => '2026-09-01 00:00:00']);
        $this->createArticle($author, ['title_jp' => 'Newer', 'created_at' => '2026-09-02 00:00:00']);

        $descending = $this->json('GET', '/api/v1/articles', ['sort' => '-created_at']);
        $this->assertSame(['Newer', 'Older'], array_column($descending->json('items'), 'title_jp'));

        $ascending = $this->json('GET', '/api/v1/articles', ['sort' => 'created_at']);
        $this->assertSame(['Older', 'Newer'], array_column($ascending->json('items'), 'title_jp'));
    }

    public function test_index_filters_by_inclusive_creation_dates(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'InRange', 'created_at' => '2026-09-08 23:30:00']);
        $this->createArticle($author, ['title_jp' => 'OutOfRange', 'created_at' => '2026-09-09 00:30:00']);

        $response = $this->json('GET', '/api/v1/articles', [
            'created_from' => '2026-09-08',
            'created_to' => '2026-09-08',
        ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['InRange']);
    }

    public function test_index_rejects_a_reversed_date_range(): void
    {
        $this->json('GET', '/api/v1/articles', [
            'created_from' => '2026-09-09',
            'created_to' => '2026-09-08',
        ])->assertStatus(422)->assertJsonValidationErrors(['created_from']);
    }

    public function test_index_rejects_more_than_twenty_filter_values(): void
    {
        $this->json('GET', '/api/v1/articles', ['hashtag_ids' => range(1, 21)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hashtag_ids']);
    }

    public function test_duplicate_filter_values_collapse_to_one(): void
    {
        // 21 values but only 2 unique ones: deduplication runs before the cap.
        $this->json('GET', '/api/v1/articles', ['hashtag_ids' => array_merge(array_fill(0, 20, 3), [4])])
            ->assertStatus(200);
    }

    public function test_index_rejects_a_page_beyond_the_maximum_offset(): void
    {
        $this->json('GET', '/api/v1/articles', ['page' => 2000, 'per_page' => 100])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    public function test_index_rejects_a_single_character_search(): void
    {
        $this->json('GET', '/api/v1/articles', ['q' => 'a'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }
    // ------------------------------------------------------- AFM-05 facets

    public function test_facets_are_absent_by_default_but_the_key_is_stable(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any', 'n5' => 1]);

        $response = $this->json('GET', '/api/v1/articles');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('facets'), 'facets must be an empty array, not missing');
    }

    public function test_facets_are_returned_when_requested(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any', 'n5' => 2]);

        $response = $this->json('GET', '/api/v1/articles', ['include_facets' => true]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'facets' => [['key', 'label', 'type', 'values' => [['key', 'label', 'count', 'selected']]]],
        ]);

        $keys = array_column($response->json('facets'), 'key');
        $this->assertSame(['jlpt_levels', 'hashtag_ids'], $keys);
    }

    public function test_facet_counts_do_not_leak_private_articles_to_guests(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Public', 'n5' => 1]);
        $this->createArticle($author, ['title_jp' => 'Private', 'n5' => 1, 'publicity' => PublicityStatus::PRIVATE]);

        $response = $this->json('GET', '/api/v1/articles', ['include_facets' => true]);

        $facets = collect($response->json('facets'))->firstWhere('key', 'jlpt_levels');
        $n5 = collect($facets['values'])->firstWhere('key', 'n5');

        $this->assertSame(1, $n5['count'], 'A private Article leaked through the facet count');
    }

    public function test_the_applied_echo_contains_canonical_intent_only(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any', 'n5' => 1]);

        $response = $this->json('GET', '/api/v1/articles', [
            'q' => 'Any',
            'jlpt_levels' => ['n5'],
            'sort' => 'created_at',
        ]);

        $response->assertStatus(200);
        $this->assertSame('Any', $response->json('applied.q'));
        $this->assertSame(['n5'], $response->json('applied.filters.jlpt_levels'));
        $this->assertSame('created_at', $response->json('applied.sort'));
        $this->assertNull($response->json('applied.filters.created_from'));
    }

    public function test_the_applied_echo_includes_the_date_range(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any']);

        $response = $this->json('GET', '/api/v1/articles', [
            'created_from' => '2026-01-01',
            'created_to' => '2026-06-30',
        ]);

        $response->assertStatus(200);
        $this->assertSame('2026-01-01', $response->json('applied.filters.created_from'));
        $this->assertSame('2026-06-30', $response->json('applied.filters.created_to'));
    }

    /**
     * AFM-07 retired the compatibility aliases once a caller scan showed nothing sent
     * them. They are now unknown keys, which the endpoint rejects like any other.
     */
    #[DataProvider('retiredAliasProvider')]
    public function test_retired_compatibility_aliases_are_rejected(string $alias, mixed $value): void
    {
        $this->json('GET', '/api/v1/articles', [$alias => $value])
            ->assertStatus(422)
            ->assertJsonValidationErrors([$alias]);
    }

    public static function retiredAliasProvider(): array
    {
        return [
            'search' => ['search', 'grammar'],
            'category' => ['category', 5],
            'sort_by' => ['sort_by', 'created_at'],
            'sort_dir' => ['sort_dir', 'asc'],
        ];
    }

    public function test_the_applied_echo_never_contains_visibility(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any']);

        $echo = $this->json('GET', '/api/v1/articles')->json('applied');

        $flattened = json_encode($echo);
        $this->assertStringNotContainsString('publicity', $flattened);
        $this->assertStringNotContainsString('scope', $flattened);
        $this->assertStringNotContainsString('visibility', $flattened);
    }

    public function test_existing_items_and_pagination_fields_remain_compatible(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any']);

        $this->json('GET', '/api/v1/articles')
            ->assertStatus(200)
            ->assertJsonStructure([
                'items' => [['id', 'uuid', 'title_jp']],
                'pagination' => ['page', 'per_page', 'total', 'last_page', 'has_more'],
            ]);
    }
}

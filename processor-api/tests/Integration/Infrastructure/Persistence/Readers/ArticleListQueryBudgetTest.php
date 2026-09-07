<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Readers;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Guards the Article list query budget.
 *
 * The value here is not the absolute number, it is that the number does not grow
 * with the page size. An N+1 introduced in enrichment is invisible on a 2-item test
 * fixture and catastrophic on a 100-item page, so these tests compare the two.
 */
class ArticleListQueryBudgetTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    /**
     * Ceiling for a no-facet list with every enrichment projection on.
     *
     * AFM-04 proposed 8 before anything was measured. The measured figure is 11, all
     * batched and all constant in page size:
     *
     *   1  count for pagination
     *   2  the Article page itself
     *   3  users eager load
     *   4  kanjis eager load
     *   5  likes aggregate
     *   6  downloads aggregate
     *   7  views aggregate
     *   8  comments aggregate
     *   9  objecttemplates id lookup
     *   10 hashtag_entity links
     *   11 last_operations processing state
     *
     * Two reductions are available and both sit outside Article filtering:
     * queries 5-8 are four separate aggregates in the Engagement module that could be
     * one grouped query, and query 9 resolves a constant that rarely changes and
     * could be cached. Raising this ceiling needs recorded justification, not a quiet
     * edit.
     */
    private const MAX_QUERIES = 11;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();
    }

    public function test_enriched_listing_uses_a_constant_number_of_queries_as_the_page_grows(): void
    {
        $this->seedArticles(30);

        $small = $this->countQueriesForListing(perPage: 2);
        $large = $this->countQueriesForListing(perPage: 25);

        $this->assertSame(
            $small,
            $large,
            "Query count grew from {$small} to {$large} as page size increased: enrichment is not batched",
        );
    }

    public function test_enriched_listing_stays_within_the_agreed_query_budget(): void
    {
        $this->seedArticles(25);

        $queries = $this->countQueriesForListing(perPage: 25);

        $this->assertLessThanOrEqual(
            self::MAX_QUERIES,
            $queries,
            "Article listing used {$queries} queries, above the agreed budget of ".self::MAX_QUERIES,
        );
    }

    /**
     * A projection that switches enrichment off must actually cost less, otherwise
     * include_* flags are decorative and internal callers pay for data they discard.
     */
    public function test_disabling_enrichment_reduces_the_query_count(): void
    {
        $this->seedArticles(10);

        $enriched = $this->countQueriesForListing(perPage: 10);
        $lean = $this->countQueriesForListing(perPage: 10, params: [
            'include_stats_counts' => false,
            'include_hashtags' => false,
            'include_kanjis' => false,
            'include_words' => false,
        ]);

        $this->assertLessThan($enriched, $lean);
    }

    #[DataProvider('filterProvider')]
    public function test_filters_do_not_add_per_row_queries(array $params): void
    {
        $this->seedArticles(20);

        $unfiltered = $this->countQueriesForListing(perPage: 20);
        $filtered = $this->countQueriesForListing(perPage: 20, params: $params);

        $this->assertLessThanOrEqual($unfiltered, $filtered);
    }

    public static function filterProvider(): array
    {
        return [
            'jlpt levels' => [['jlpt_levels' => ['n5', 'n2']]],
            'search' => [['q' => 'Article']],
            'sort by title' => [['sort' => 'title_jp']],
            'date range' => [['created_from' => '2020-01-01', 'created_to' => '2030-01-01']],
        ];
    }

    private function countQueriesForListing(int $perPage, array $params = []): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->json('GET', '/api/v1/articles', array_merge(['per_page' => $perPage], $params))
            ->assertStatus(200);

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function seedArticles(int $count): void
    {
        $author = User::factory()->create();

        for ($i = 1; $i <= $count; $i++) {
            PersistenceArticle::factory()->byUser($author)->create([
                'title_jp' => 'Article '.$i,
                'title_en' => 'Article '.$i,
                'publicity' => PublicityStatus::PUBLIC,
                'status' => ArticleStatus::PENDING,
                'n5' => $i % 3,
                'n2' => $i % 2,
            ]);
        }
    }
}

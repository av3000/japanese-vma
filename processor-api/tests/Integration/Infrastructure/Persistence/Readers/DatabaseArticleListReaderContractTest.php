<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Readers;

use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Articles\ValueObjects\ArticleDateRange;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * The semantic contract every ArticleListReaderInterface implementation must
 * satisfy. It is written against the interface, not DatabaseArticleListReader, so a
 * future search-engine reader can be held to the same behaviour.
 *
 * Runs on PostgreSQL 17, the only supported engine.
 */
class DatabaseArticleListReaderContractTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private ArticleListReaderInterface $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();

        $this->reader = app(ArticleListReaderInterface::class);
    }

    // ---------------------------------------------------------------- visibility

    public function test_public_only_scope_excludes_every_private_article(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Public']);
        $this->createArticle($author, ['title_jp' => 'Private', 'publicity' => PublicityStatus::PRIVATE]);

        $this->assertTitles(['Public'], $this->read(scope: ArticleVisibilityScope::publicOnly()));
    }

    public function test_public_or_owned_scope_admits_only_the_actors_own_private_articles(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();

        $this->createArticle($owner, ['title_jp' => 'Mine Public']);
        $this->createArticle($owner, ['title_jp' => 'Mine Private', 'publicity' => PublicityStatus::PRIVATE]);
        $this->createArticle($stranger, ['title_jp' => 'Theirs Private', 'publicity' => PublicityStatus::PRIVATE]);

        $this->assertTitles(
            ['Mine Private', 'Mine Public'],
            $this->read(scope: ArticleVisibilityScope::publicOrOwnedBy($owner->id)),
        );
    }

    public function test_unrestricted_scope_admits_everything(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $this->createArticle($a, ['title_jp' => 'One']);
        $this->createArticle($b, ['title_jp' => 'Two', 'publicity' => PublicityStatus::PRIVATE]);

        $this->assertTitles(['One', 'Two'], $this->read(scope: ArticleVisibilityScope::unrestricted()));
    }

    /**
     * A filter must narrow the scope, never widen it. If an OR group leaked out of
     * its closure it could pull private rows past the visibility predicate.
     */
    public function test_a_multi_value_filter_cannot_widen_the_visibility_scope(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();

        $this->createArticle($owner, ['title_jp' => 'Mine Public', 'n5' => 3]);
        $this->createArticle($stranger, ['title_jp' => 'Theirs Private', 'publicity' => PublicityStatus::PRIVATE, 'n4' => 2]);

        $result = $this->read(
            scope: ArticleVisibilityScope::publicOnly(),
            jlptLevels: [ArticleJlptLevel::N5, ArticleJlptLevel::N4],
        );

        $this->assertTitles(['Mine Public'], $result);
    }

    // ---------------------------------------------------------------------- jlpt

    public function test_jlpt_levels_are_ored_within_the_dimension(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Has N5', 'n5' => 4]);
        $this->createArticle($author, ['title_jp' => 'Has N2', 'n2' => 1]);
        $this->createArticle($author, ['title_jp' => 'Has N1', 'n1' => 7]);

        $this->assertTitles(
            ['Has N2', 'Has N5'],
            $this->read(jlptLevels: [ArticleJlptLevel::N5, ArticleJlptLevel::N2]),
        );
    }

    public function test_a_zero_count_does_not_match_a_level(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'No N1', 'n1' => 0, 'n5' => 2]);

        $this->assertTitles([], $this->read(jlptLevels: [ArticleJlptLevel::N1]));
    }

    // ------------------------------------------------------------------- hashtag

    public function test_hashtag_filter_matches_unique_hashtag_ids_and_ors_within_the_dimension(): void
    {
        $author = $this->createUser();
        $tagged = $this->createArticle($author, ['title_jp' => 'Tagged A']);
        $alsoTagged = $this->createArticle($author, ['title_jp' => 'Tagged B']);
        $this->createArticle($author, ['title_jp' => 'Untagged']);

        $first = $this->createHashtag('grammar');
        $second = $this->createHashtag('kanji');
        $this->attachHashtag($tagged, $first);
        $this->attachHashtag($alsoTagged, $second);

        $this->assertTitles(['Tagged A', 'Tagged B'], $this->read(hashtagIds: [$first, $second]));
        $this->assertTitles(['Tagged A'], $this->read(hashtagIds: [$first]));
    }

    public function test_soft_deleted_hashtag_links_do_not_match(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author, ['title_jp' => 'Was Tagged']);
        $hashtagId = $this->createHashtag('removed');
        $this->attachHashtag($article, $hashtagId);

        $this->assertTitles(['Was Tagged'], $this->read(hashtagIds: [$hashtagId]));

        DB::table('hashtag_entity')
            ->where('entity_id', $article->id)
            ->update(['deleted_at' => now()]);

        $this->assertTitles([], $this->read(hashtagIds: [$hashtagId]));
    }

    public function test_hashtag_links_belonging_to_other_entity_types_do_not_match(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author, ['title_jp' => 'Article']);
        $hashtagId = $this->createHashtag('shared');

        // Same hashtag id, but linked to a Post rather than this Article.
        DB::table('hashtag_entity')->insert([
            'entity_type_id' => ObjectTemplateType::POST->getLegacyId(),
            'entity_id' => $article->id,
            'hashtag_id' => $hashtagId,
            'user_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTitles([], $this->read(hashtagIds: [$hashtagId]));
    }

    // -------------------------------------------------------------------- search

    public function test_title_search_is_case_insensitive(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => '日本語', 'title_en' => 'Japanese Grammar Guide']);

        $this->assertTitles(['日本語'], $this->read(search: 'grammar'));
        $this->assertTitles(['日本語'], $this->read(search: 'GRAMMAR'));
    }

    public function test_search_matches_either_title_language(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => '漢字の本', 'title_en' => 'Kanji Book']);
        $this->createArticle($author, ['title_jp' => 'ひらがな', 'title_en' => 'Hiragana']);

        $this->assertTitles(['漢字の本'], $this->read(search: '漢字'));
        $this->assertTitles(['漢字の本'], $this->read(search: 'Kanji'));
    }

    /**
     * A user searching for "50%" means the literal characters. Unescaped, the % would
     * become a wildcard and match everything.
     */
    public function test_search_wildcards_are_treated_as_literal_text(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Discount', 'title_en' => '50% off']);
        $this->createArticle($author, ['title_jp' => 'Unrelated', 'title_en' => 'Nothing here']);

        $this->assertTitles(['Discount'], $this->read(search: '50%'));
        $this->assertTitles([], $this->read(search: '%zzz%'));
        // A bare "_" would match any single character if left unescaped.
        $this->assertTitles([], $this->read(search: '_x'));
    }

    // --------------------------------------------------------------- combination

    public function test_different_dimensions_are_anded_together(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Match', 'title_en' => 'Grammar', 'n5' => 2]);
        $this->createArticle($author, ['title_jp' => 'Wrong Level', 'title_en' => 'Grammar', 'n1' => 2]);
        $this->createArticle($author, ['title_jp' => 'Wrong Text', 'title_en' => 'Kanji', 'n5' => 2]);

        $this->assertTitles(
            ['Match'],
            $this->read(search: 'grammar', jlptLevels: [ArticleJlptLevel::N5]),
        );
    }

    public function test_date_bounds_are_inclusive_at_both_ends_of_the_day(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Early', 'created_at' => '2026-09-08 00:00:00']);
        $this->createArticle($author, ['title_jp' => 'Late', 'created_at' => '2026-09-08 23:59:59']);
        $this->createArticle($author, ['title_jp' => 'NextDay', 'created_at' => '2026-09-09 00:00:01']);

        $this->assertTitles(
            ['Early', 'Late'],
            $this->read(createdBetween: ArticleDateRange::fromInput('2026-09-08', '2026-09-08')),
        );
    }

    // -------------------------------------------------------------------- sorting

    /**
     * Without the id tie-breaker, rows sharing a created_at can reorder between
     * requests and a user paging through the list sees duplicates and gaps.
     */
    public function test_sorting_is_deterministic_when_the_primary_value_ties(): void
    {
        $author = $this->createUser();

        foreach (['AA', 'BB', 'CC', 'DD'] as $title) {
            $this->createArticle($author, ['title_jp' => $title, 'created_at' => '2026-09-08 12:00:00']);
        }

        $firstPage = $this->read(pagination: new Pagination(1, 2));
        $secondPage = $this->read(pagination: new Pagination(2, 2));

        $seen = array_merge($this->titles($firstPage), $this->titles($secondPage));

        $this->assertCount(4, $seen);
        $this->assertSame($seen, array_unique($seen), 'A row appeared on more than one page');

        // Stable across repeated identical requests.
        $this->assertSame($this->titles($firstPage), $this->titles($this->read(pagination: new Pagination(1, 2))));
    }

    public function test_sort_direction_is_honoured(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Alpha', 'created_at' => '2026-09-01 00:00:00']);
        $this->createArticle($author, ['title_jp' => 'Beta', 'created_at' => '2026-09-02 00:00:00']);

        $this->assertSame(['Beta', 'Alpha'], $this->titles($this->read(sort: ArticleSortCriteria::fromSigned('-created_at'))));
        $this->assertSame(['Alpha', 'Beta'], $this->titles($this->read(sort: ArticleSortCriteria::fromSigned('created_at'))));
    }

    // ----------------------------------------------------------------- pagination

    public function test_pagination_metadata_reflects_the_scoped_total(): void
    {
        $author = $this->createUser();
        $stranger = $this->createUser();

        foreach (range(1, 3) as $i) {
            $this->createArticle($author, ['title_jp' => "Public {$i}"]);
        }
        $this->createArticle($stranger, ['title_jp' => 'Hidden', 'publicity' => PublicityStatus::PRIVATE]);

        $result = $this->read(scope: ArticleVisibilityScope::publicOnly(), pagination: new Pagination(1, 2));

        $this->assertSame(3, $result->pagination->total, 'Total must exclude rows the scope hides');
        $this->assertSame(2, $result->pagination->lastPage);
        $this->assertTrue($result->pagination->hasMore);
    }

    // ------------------------------------------------------------ list without total

    public function test_list_without_total_returns_the_same_rows_in_the_same_order_as_search(): void
    {
        $author = User::factory()->create();
        foreach (['Alpha', 'Bravo', 'Charlie'] as $title) {
            $this->createArticle($author, ['title_jp' => $title]);
        }

        $criteria = ArticleQueryCriteria::forListing(
            page: 1,
            perPage: 2,
            sort: ArticleSortCriteria::fromSigned('title_jp'),
        );
        $scope = ArticleVisibilityScope::unrestricted();

        $page = $this->reader->search($criteria, $scope, ArticleListIncludes::itemsOnly());
        $rows = $this->reader->listWithoutTotal($criteria, $scope, ArticleListIncludes::itemsOnly());

        $this->assertSame(['Alpha', 'Bravo'], $this->titles($page));
        $this->assertSame(
            $this->titles($page),
            array_map(static fn (DomainArticle $article): string => $article->getTitleJp()->value, $rows),
        );
    }

    // --------------------------------------------------------------------- helpers

    private function read(
        ?ArticleVisibilityScope $scope = null,
        ?string $search = null,
        array $jlptLevels = [],
        array $hashtagIds = [],
        array $kanjiIds = [],
        array $wordIds = [],
        ?ArticleDateRange $createdBetween = null,
        ?ArticleSortCriteria $sort = null,
        ?Pagination $pagination = null,
    ) {
        $criteria = new ArticleQueryCriteria(
            sort: $sort ?? ArticleSortCriteria::fromSigned('created_at'),
            pagination: $pagination ?? Pagination::default(),
            search: $search !== null ? SearchTerm::fromInputOrNull($search) : null,
            jlptLevels: $jlptLevels,
            hashtagIds: $hashtagIds,
            kanjiIds: $kanjiIds,
            wordIds: $wordIds,
            createdBetween: $createdBetween,
        );

        return $this->reader->search(
            $criteria,
            $scope ?? ArticleVisibilityScope::unrestricted(),
            ArticleListIncludes::itemsOnly(),
        );
    }

    private function assertTitles(array $expected, $result): void
    {
        $actual = $this->titles($result);
        sort($actual);
        sort($expected);

        $this->assertSame($expected, $actual);
    }

    private function titles($result): array
    {
        return array_map(
            static fn (DomainArticle $article): string => $article->getTitleJp()->value,
            $result->articles,
        );
    }

    private function createUser(): User
    {
        return User::factory()->create();
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

    private function createHashtag(string $name): int
    {
        return (int) DB::table('uniquehashtags')->insertGetId([
            'content' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachHashtag(PersistenceArticle $article, int $hashtagId): void
    {
        DB::table('hashtag_entity')->insert([
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'entity_id' => $article->id,
            'hashtag_id' => $hashtagId,
            'user_id' => $article->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

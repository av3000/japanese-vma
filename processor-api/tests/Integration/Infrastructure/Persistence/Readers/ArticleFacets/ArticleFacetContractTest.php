<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Readers\ArticleFacets;

use App\Application\Articles\DTOs\ArticleFacetDTO;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\ValueObjects\ArticleListSort;
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
 * Facet counts share the eligibility predicate with the items they describe.
 *
 * The leak these tests exist to prevent: a count built from a slightly different
 * predicate reveals Articles the viewer is not allowed to see. The row stays hidden
 * but "N2 (13)" still tells them it exists.
 */
class ArticleFacetContractTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private ArticleListReaderInterface $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();

        $this->reader = app(ArticleListReaderInterface::class);
    }

    // -------------------------------------------------------------- leak guards

    public function test_jlpt_counts_exclude_articles_the_scope_hides(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();

        $this->createArticle($owner, ['title_jp' => 'Public N5', 'n5' => 2]);
        $this->createArticle($stranger, ['title_jp' => 'Private N5', 'n5' => 2, 'publicity' => PublicityStatus::PRIVATE]);

        $this->assertSame(1, $this->jlptCount('n5', ArticleVisibilityScope::publicOnly()));
        $this->assertSame(2, $this->jlptCount('n5', ArticleVisibilityScope::unrestricted()));
    }

    public function test_jlpt_counts_include_the_actors_own_private_articles_only(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();

        $this->createArticle($owner, ['title_jp' => 'Mine Private', 'n2' => 1, 'publicity' => PublicityStatus::PRIVATE]);
        $this->createArticle($stranger, ['title_jp' => 'Theirs Private', 'n2' => 1, 'publicity' => PublicityStatus::PRIVATE]);

        $this->assertSame(1, $this->jlptCount('n2', ArticleVisibilityScope::publicOrOwnedBy($owner->id)));
        $this->assertSame(0, $this->jlptCount('n2', ArticleVisibilityScope::publicOnly()));
    }

    public function test_hashtag_counts_exclude_articles_the_scope_hides(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();

        $tag = $this->createHashtag('grammar');
        $this->attachHashtag($this->createArticle($owner, ['title_jp' => 'Public']), $tag);
        $this->attachHashtag(
            $this->createArticle($stranger, ['title_jp' => 'Private', 'publicity' => PublicityStatus::PRIVATE]),
            $tag,
        );

        $this->assertSame(1, $this->hashtagCount($tag, ArticleVisibilityScope::publicOnly()));
        $this->assertSame(2, $this->hashtagCount($tag, ArticleVisibilityScope::unrestricted()));
    }

    // ------------------------------------------------------------- disjunctive

    /**
     * The defining behaviour of a disjunctive facet: selecting N5 must not collapse
     * the JLPT list to just N5, or the user can never widen their own selection.
     */
    public function test_selecting_a_jlpt_level_does_not_shrink_the_other_jlpt_counts(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Only N5', 'n5' => 2]);
        $this->createArticle($author, ['title_jp' => 'Only N2', 'n2' => 2]);

        $unfiltered = $this->jlptFacet();
        $filtered = $this->jlptFacet(jlptLevels: [ArticleJlptLevel::N5]);

        $this->assertSame(
            $this->countsByKey($unfiltered),
            $this->countsByKey($filtered),
            'JLPT counts changed when a JLPT level was selected: the facet is not disjunctive',
        );
    }

    /**
     * Every other dimension still applies, though. Only the facet's own selection is
     * excluded.
     */
    public function test_a_different_dimension_still_narrows_the_jlpt_counts(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Grammar', 'title_en' => 'Grammar', 'n5' => 2]);
        $this->createArticle($author, ['title_jp' => 'Kanji', 'title_en' => 'Kanji', 'n5' => 2]);

        $this->assertSame(2, $this->jlptCount('n5'));
        $this->assertSame(1, $this->jlptCount('n5', search: 'grammar'));
    }

    public function test_selecting_a_hashtag_does_not_shrink_the_other_hashtag_counts(): void
    {
        $author = $this->createUser();
        $first = $this->createHashtag('alpha');
        $second = $this->createHashtag('beta');

        $this->attachHashtag($this->createArticle($author, ['title_jp' => 'A']), $first);
        $this->attachHashtag($this->createArticle($author, ['title_jp' => 'B']), $second);

        $this->assertSame(1, $this->hashtagCount($second));
        $this->assertSame(1, $this->hashtagCount($second, hashtagIds: [$first]));
    }

    // ------------------------------------------------------------------ shape

    public function test_jlpt_values_use_the_easiest_to_hardest_display_order(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any']);

        $keys = array_map(
            static fn ($value): string => $value->key,
            $this->jlptFacet()->values,
        );

        $this->assertSame(['n5', 'n4', 'n3', 'n2', 'n1', 'uncommon'], $keys);
    }

    public function test_every_jlpt_level_is_returned_even_at_zero(): void
    {
        $this->createArticle($this->createUser(), ['title_jp' => 'Any', 'n5' => 1]);

        $this->assertCount(6, $this->jlptFacet()->values);
        $this->assertSame(0, $this->jlptCount('n1'));
    }

    public function test_a_selected_value_stays_visible_when_its_count_is_zero(): void
    {
        $author = $this->createUser();
        $orphan = $this->createHashtag('never-used');
        $used = $this->createHashtag('used');
        $this->attachHashtag($this->createArticle($author, ['title_jp' => 'Tagged']), $used);

        $facet = $this->hashtagFacet(hashtagIds: [$orphan]);

        $value = $this->valueFor($facet, (string) $orphan);

        $this->assertNotNull($value, 'A selected hashtag disappeared once its count hit zero');
        $this->assertSame(0, $value->count);
        $this->assertTrue($value->selected);
        $this->assertSame('never-used', $value->label);
    }

    public function test_selected_state_is_echoed_back(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, ['title_jp' => 'Any', 'n5' => 1]);

        $facet = $this->jlptFacet(jlptLevels: [ArticleJlptLevel::N5]);

        $this->assertTrue($this->valueFor($facet, 'n5')->selected);
        $this->assertFalse($this->valueFor($facet, 'n1')->selected);
    }

    public function test_hashtag_labels_come_from_the_authoritative_table(): void
    {
        $author = $this->createUser();
        $tag = $this->createHashtag('grammar-notes');
        $this->attachHashtag($this->createArticle($author, ['title_jp' => 'Tagged']), $tag);

        $this->assertSame('grammar-notes', $this->valueFor($this->hashtagFacet(), (string) $tag)->label);
    }

    public function test_soft_deleted_hashtag_links_are_not_counted(): void
    {
        $author = $this->createUser();
        $tag = $this->createHashtag('removed');
        $article = $this->createArticle($author, ['title_jp' => 'Tagged']);
        $this->attachHashtag($article, $tag);

        $this->assertSame(1, $this->hashtagCount($tag));

        DB::table('hashtag_entity')->where('entity_id', $article->id)->update(['deleted_at' => now()]);

        $this->assertSame(0, $this->hashtagCount($tag));
    }

    public function test_links_from_other_entity_types_are_not_counted(): void
    {
        $author = $this->createUser();
        $tag = $this->createHashtag('shared');
        $article = $this->createArticle($author, ['title_jp' => 'Article']);

        DB::table('hashtag_entity')->insert([
            'entity_type_id' => ObjectTemplateType::POST->getLegacyId(),
            'entity_id' => $article->id,
            'hashtag_id' => $tag,
            'user_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $this->hashtagCount($tag));
    }

    public function test_hashtags_are_ordered_by_count_then_stable_key(): void
    {
        $author = $this->createUser();
        $popular = $this->createHashtag('popular');
        $rareA = $this->createHashtag('rare-a');
        $rareB = $this->createHashtag('rare-b');

        foreach (range(1, 3) as $i) {
            $this->attachHashtag($this->createArticle($author, ['title_jp' => 'P'.$i]), $popular);
        }
        $this->attachHashtag($this->createArticle($author, ['title_jp' => 'A']), $rareA);
        $this->attachHashtag($this->createArticle($author, ['title_jp' => 'B']), $rareB);

        $keys = array_map(static fn ($v): string => $v->key, $this->hashtagFacet()->values);

        $this->assertSame([(string) $popular, (string) $rareA, (string) $rareB], $keys);
    }

    // ------------------------------------------------------------------ budget

    public function test_facet_cost_is_independent_of_page_size(): void
    {
        $author = $this->createUser();
        $tag = $this->createHashtag('bulk');

        foreach (range(1, 20) as $i) {
            $this->attachHashtag($this->createArticle($author, ['title_jp' => 'A'.$i, 'n5' => 1]), $tag);
        }

        $small = $this->countFacetQueries(new Pagination(1, 2));
        $large = $this->countFacetQueries(new Pagination(1, 20));

        $this->assertSame($small, $large);
        $this->assertLessThanOrEqual(3, $large, 'Facet aggregation should be one bounded query per dimension');
    }

    // ----------------------------------------------------------------- helpers

    private function countFacetQueries(Pagination $pagination): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->reader->facets($this->query(pagination: $pagination), ArticleVisibilityScope::unrestricted());
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function query(
        array $jlptLevels = [],
        array $hashtagIds = [],
        ?string $search = null,
        ?Pagination $pagination = null,
    ): ArticleListQuery {
        return new ArticleListQuery(
            sort: ArticleListSort::default(),
            pagination: $pagination ?? Pagination::default(),
            search: $search !== null ? SearchTerm::fromInputOrNull($search) : null,
            jlptLevels: $jlptLevels,
            hashtagIds: $hashtagIds,
        );
    }

    private function jlptFacet(array $jlptLevels = [], array $hashtagIds = [], ?string $search = null, ?ArticleVisibilityScope $scope = null): ArticleFacetDTO
    {
        return $this->reader->facets(
            $this->query($jlptLevels, $hashtagIds, $search),
            $scope ?? ArticleVisibilityScope::unrestricted(),
        )[0];
    }

    private function hashtagFacet(array $jlptLevels = [], array $hashtagIds = [], ?string $search = null, ?ArticleVisibilityScope $scope = null): ArticleFacetDTO
    {
        return $this->reader->facets(
            $this->query($jlptLevels, $hashtagIds, $search),
            $scope ?? ArticleVisibilityScope::unrestricted(),
        )[1];
    }

    private function jlptCount(string $key, ?ArticleVisibilityScope $scope = null, ?string $search = null): int
    {
        return $this->valueFor($this->jlptFacet(search: $search, scope: $scope), $key)->count;
    }

    private function hashtagCount(int $hashtagId, ?ArticleVisibilityScope $scope = null, array $hashtagIds = []): int
    {
        $value = $this->valueFor($this->hashtagFacet(hashtagIds: $hashtagIds, scope: $scope), (string) $hashtagId);

        return $value?->count ?? 0;
    }

    private function valueFor(ArticleFacetDTO $facet, string $key)
    {
        foreach ($facet->values as $value) {
            if ($value->key === $key) {
                return $value;
            }
        }

        return null;
    }

    private function countsByKey(ArticleFacetDTO $facet): array
    {
        $counts = [];
        foreach ($facet->values as $value) {
            $counts[$value->key] = $value->count;
        }

        return $counts;
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

    private function createHashtag(string $content): int
    {
        return (int) DB::table('uniquehashtags')->insertGetId([
            'content' => $content,
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

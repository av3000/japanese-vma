<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Services\ArticleListServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * MCP-10 (#717): a public article reaches guests and other users only once it is PROCESSED or
 * APPROVED, in the list, in its total and count, and on its page. The author and admins keep
 * every status.
 */
class ArticleStatusVisibilityTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const VISIBLE_TITLES = ['Status PROCESSED', 'Status APPROVED'];

    private const HIDDEN_TITLES = ['Status PENDING', 'Status REVIEWING', 'Status REJECTED'];

    private User $author;

    /** @var array<string, PersistenceArticle> */
    private array $publicByStatus = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();

        $this->author = User::factory()->create();

        foreach (ArticleStatus::cases() as $status) {
            $this->publicByStatus[$status->name] = PersistenceArticle::factory()
                ->byUser($this->author)
                ->create([
                    'title_jp' => 'Status '.$status->name,
                    'publicity' => PublicityStatus::PUBLIC,
                    'status' => $status,
                ]);
        }
    }

    // ------------------------------------------------------------------ AC-1

    public function test_a_guest_list_holds_only_processed_and_approved_public_articles(): void
    {
        $response = $this->json('GET', '/api/v1/articles');

        $response->assertStatus(200);
        $this->assertTitles($response->json('items'), self::VISIBLE_TITLES);
        $this->assertSame(2, $response->json('pagination.total'));
    }

    public function test_a_guest_cannot_reach_hidden_articles_through_the_status_filter(): void
    {
        $response = $this->json('GET', '/api/v1/articles', [
            'statuses' => [ArticleStatus::PENDING->value, ArticleStatus::REVIEWING->value, ArticleStatus::REJECTED->value],
        ]);

        $response->assertStatus(200);
        $this->assertSame([], $response->json('items'));
        $this->assertSame(0, $response->json('pagination.total'));
    }

    public function test_the_jlpt_facet_counts_agree_with_what_a_guest_sees(): void
    {
        foreach (ArticleStatus::cases() as $status) {
            $this->publicByStatus[$status->name]->update(['n5' => 3]);
        }

        $response = $this->json('GET', '/api/v1/articles', ['include_facets' => true]);

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('pagination.total'));

        $facet = collect($response->json('facets'))->firstWhere('key', 'jlpt_levels');
        $n5 = collect($facet['values'])->firstWhere('key', 'n5');

        $this->assertSame(2, $n5['count']);
    }

    // ------------------------------------------------------------------ AC-2

    public function test_a_guest_gets_a_private_articles_response_for_each_unmoderated_public_article(): void
    {
        $private = PersistenceArticle::factory()
            ->byUser($this->author)
            ->asPrivate()
            ->create(['status' => ArticleStatus::APPROVED]);

        $privateResponse = $this->json('GET', "/api/v1/articles/{$private->uuid}");
        $privateResponse->assertStatus(403);

        foreach (['PENDING', 'REVIEWING', 'REJECTED'] as $name) {
            $response = $this->json('GET', "/api/v1/articles/{$this->publicByStatus[$name]->uuid}");

            $response->assertStatus(403);
            $this->assertSame($privateResponse->json('code'), $response->json('code'), $name);
        }
    }

    public function test_a_guest_still_opens_processed_and_approved_public_articles(): void
    {
        foreach (['PROCESSED', 'APPROVED'] as $name) {
            $this->json('GET', "/api/v1/articles/{$this->publicByStatus[$name]->uuid}")
                ->assertStatus(200)
                ->assertJsonPath('uid', $this->publicByStatus[$name]->uuid);
        }
    }

    // ------------------------------------------------------------------ AC-3

    public function test_a_signed_in_user_who_is_not_the_author_gets_the_guest_results(): void
    {
        $stranger = User::factory()->create();
        Passport::actingAs($stranger, ['*'], 'api');

        $list = $this->json('GET', '/api/v1/articles');

        $list->assertStatus(200);
        $this->assertTitles($list->json('items'), self::VISIBLE_TITLES);
        $this->assertSame(2, $list->json('pagination.total'));

        foreach (['PENDING', 'REVIEWING', 'REJECTED'] as $name) {
            $this->json('GET', "/api/v1/articles/{$this->publicByStatus[$name]->uuid}")
                ->assertStatus(403);
        }

        foreach (['PROCESSED', 'APPROVED'] as $name) {
            $this->json('GET', "/api/v1/articles/{$this->publicByStatus[$name]->uuid}")
                ->assertStatus(200);
        }
    }

    public function test_the_author_sees_and_opens_their_own_article_in_every_status(): void
    {
        Passport::actingAs($this->author, ['*'], 'api');

        $list = $this->json('GET', '/api/v1/articles');

        $list->assertStatus(200);
        $this->assertTitles($list->json('items'), array_merge(self::VISIBLE_TITLES, self::HIDDEN_TITLES));
        $this->assertSame(5, $list->json('pagination.total'));

        foreach ($this->publicByStatus as $name => $article) {
            $this->json('GET', "/api/v1/articles/{$article->uuid}")->assertStatus(200);
        }
    }

    public function test_an_admin_sees_and_opens_the_authors_article_in_every_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin, ['*'], 'api');

        $list = $this->json('GET', '/api/v1/articles');

        $list->assertStatus(200);
        $this->assertTitles($list->json('items'), array_merge(self::VISIBLE_TITLES, self::HIDDEN_TITLES));
        $this->assertSame(5, $list->json('pagination.total'));

        foreach ($this->publicByStatus as $article) {
            $this->json('GET', "/api/v1/articles/{$article->uuid}")->assertStatus(200);
        }
    }

    public function test_an_author_keeps_their_private_articles_next_to_their_public_ones(): void
    {
        PersistenceArticle::factory()
            ->byUser($this->author)
            ->asPrivate()
            ->create(['title_jp' => 'Private rejected', 'status' => ArticleStatus::REJECTED]);

        Passport::actingAs($this->author, ['*'], 'api');

        $list = $this->json('GET', '/api/v1/articles');

        $this->assertSame(6, $list->json('pagination.total'));
        $this->assertContains('Private rejected', array_column($list->json('items'), 'title_jp'));
    }

    // ------------------------------------------------------------------ AC-4

    public function test_the_guest_count_matches_the_guest_list_total(): void
    {
        $listTotal = $this->json('GET', '/api/v1/articles')->json('pagination.total');

        $count = app(ArticleListServiceInterface::class)
            ->count(ArticleQueryCriteria::forListing(perPage: 5), null)
            ->getData();

        $this->assertSame(2, $listTotal);
        $this->assertSame($listTotal, $count);
    }

    public function test_the_count_for_a_non_author_matches_the_guest_count_and_the_authors_own_count_includes_everything(): void
    {
        $service = app(ArticleListServiceInterface::class);
        $criteria = ArticleQueryCriteria::forListing(perPage: 5);

        $this->assertSame(2, $service->count($criteria, $this->actor(User::factory()->create()))->getData());
        $this->assertSame(5, $service->count($criteria, $this->actor($this->author))->getData());
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string> $expected
     */
    private function assertTitles(array $items, array $expected): void
    {
        $actual = array_column($items, 'title_jp');
        sort($actual);
        sort($expected);

        $this->assertSame($expected, $actual);
    }

    private function actor(User $user): AuthenticatedUser
    {
        return new AuthenticatedUser(
            UserId::from($user->id),
            EntityId::from($user->uuid),
            UserName::from($user->name),
            false,
        );
    }
}

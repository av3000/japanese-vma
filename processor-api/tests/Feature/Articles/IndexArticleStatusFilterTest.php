<?php

namespace Tests\Feature\Articles;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * UI-DASH-01 (#451): `statuses[]` narrows the Article list by moderation status. It is user
 * intent only, so it can never widen the visibility scope the actor already has.
 */
class IndexArticleStatusFilterTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    private function createArticle(User $user, string $title, ArticleStatus $status, PublicityStatus $publicity = PublicityStatus::PUBLIC): void
    {
        PersistenceArticle::factory()
            ->byUser($user)
            ->create([
                'title_jp' => $title,
                'publicity' => $publicity,
                'status' => $status,
            ]);
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

    public function test_a_single_status_returns_only_matching_articles(): void
    {
        $author = User::factory()->create();

        foreach (ArticleStatus::cases() as $status) {
            $this->createArticle($author, 'Status '.$status->value, $status);
        }

        $response = $this->json('GET', '/api/v1/articles', ['statuses' => [ArticleStatus::REJECTED->value]]);

        $response->assertStatus(200);
        $this->assertTitles($response->json('items'), ['Status 3']);
        $this->assertSame(1, $response->json('pagination.total'));
    }

    public function test_several_statuses_are_or_within_the_dimension(): void
    {
        $author = User::factory()->create();

        foreach (ArticleStatus::cases() as $status) {
            $this->createArticle($author, 'Status '.$status->value, $status);
        }

        $response = $this->json('GET', '/api/v1/articles', [
            'statuses' => [ArticleStatus::PENDING->value, ArticleStatus::REVIEWING->value],
        ]);

        $response->assertStatus(200);
        $this->assertTitles($response->json('items'), ['Status 0', 'Status 2']);
        $this->assertSame(2, $response->json('pagination.total'));
    }

    public function test_the_filter_never_shows_a_guest_someone_elses_private_article(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, 'Public pending', ArticleStatus::PENDING);
        $this->createArticle($author, 'Private pending', ArticleStatus::PENDING, PublicityStatus::PRIVATE);

        $response = $this->json('GET', '/api/v1/articles', ['statuses' => [ArticleStatus::PENDING->value]]);

        $response->assertStatus(200);
        $this->assertTitles($response->json('items'), ['Public pending']);
    }

    public function test_the_owner_filters_their_own_private_articles(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->createArticle($owner, 'Mine rejected private', ArticleStatus::REJECTED, PublicityStatus::PRIVATE);
        $this->createArticle($owner, 'Mine approved', ArticleStatus::APPROVED);
        $this->createArticle($other, 'Theirs rejected', ArticleStatus::REJECTED);
        $this->createArticle($other, 'Theirs rejected private', ArticleStatus::REJECTED, PublicityStatus::PRIVATE);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/articles', [
            'author_uid' => $owner->uuid,
            'statuses' => [ArticleStatus::REJECTED->value],
        ]);

        $response->assertStatus(200);
        $this->assertTitles($response->json('items'), ['Mine rejected private']);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $this->json('GET', '/api/v1/articles', ['statuses' => [9]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['statuses.0']);
    }

    public function test_a_non_integer_status_is_rejected(): void
    {
        $this->json('GET', '/api/v1/articles', ['statuses' => ['rejected']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['statuses.0']);
    }

    public function test_duplicates_collapse_before_the_cap_and_a_scalar_is_rejected(): void
    {
        // 25 copies of one value: deduplication runs before the 20-value cap.
        $this->json('GET', '/api/v1/articles', ['statuses' => array_fill(0, 25, ArticleStatus::PENDING->value)])
            ->assertStatus(200);

        $this->json('GET', '/api/v1/articles', ['statuses' => 'not-an-array'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['statuses']);
    }

    public function test_the_applied_echo_lists_the_statuses_as_integers(): void
    {
        $response = $this->json('GET', '/api/v1/articles', [
            'statuses' => [(string) ArticleStatus::REVIEWING->value, ArticleStatus::PENDING->value],
        ]);

        $response->assertStatus(200);
        $this->assertSame([2, 0], $response->json('applied.filters.statuses'));
    }

    public function test_the_applied_echo_is_an_empty_list_without_the_filter(): void
    {
        $response = $this->json('GET', '/api/v1/articles');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('applied.filters.statuses'));
    }
}

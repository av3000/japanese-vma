<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * The guest read the MCP `read_article` tool uses: the same visibility rule as `getArticle`, and no view.
 */
final class FindPublicArticleTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->author = User::factory()->create();
    }

    /**
     * @return array<string, array{ArticleStatus}>
     */
    public static function visibleStatuses(): array
    {
        return [
            'processed' => [ArticleStatus::PROCESSED],
            'approved' => [ArticleStatus::APPROVED],
        ];
    }

    #[DataProvider('visibleStatuses')]
    public function test_a_public_processed_or_approved_article_is_returned(ArticleStatus $status): void
    {
        $article = $this->createArticle(['status' => $status, 'title_jp' => '公開の記事']);

        $result = $this->service()->findPublicArticle(EntityId::from($article->uuid));

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(DomainArticle::class, $result->getData());
        $this->assertSame($article->uuid, $result->getData()->getUid()->value());
        $this->assertSame('公開の記事', (string) $result->getData()->getTitleJp());
    }

    public function test_a_private_article_a_hidden_status_and_a_missing_article_fail_with_the_same_error(): void
    {
        $uuids = [Str::uuid()->toString() => 'missing'];

        $private = $this->createArticle(['publicity' => PublicityStatus::PRIVATE]);
        $uuids[$private->uuid] = 'private';

        foreach (ArticleStatus::cases() as $status) {
            if ($status->isPubliclyVisible()) {
                continue;
            }

            $hidden = $this->createArticle(['status' => $status]);
            $uuids[$hidden->uuid] = "public {$status->name}";
        }

        $this->assertGreaterThanOrEqual(5, count($uuids));

        foreach ($uuids as $uuid => $case) {
            $result = $this->service()->findPublicArticle(EntityId::from($uuid));

            $this->assertTrue($result->isFailure(), $case);
            $this->assertSame('Articles.NotFound', $result->getError()->code, $case);
            // The message carries the id the caller asked for and nothing about why the article is hidden.
            $this->assertSame("Article with ID {$uuid} does not exist", $result->getError()->errorMessage, $case);
        }
    }

    public function test_reading_adds_no_view_while_the_website_read_adds_one(): void
    {
        $article = $this->createArticle();
        $uid = EntityId::from($article->uuid);

        $this->service()->findPublicArticle($uid);
        $this->service()->findPublicArticle($uid);

        $this->assertSame(0, View::query()->count());

        // The control: the website's own read of the same article does record a view, so the count above is meaningful.
        $this->service()->getArticle($uid, new ArticleIncludeOptionsDTO(), new Viewer(null, '203.0.113.9'));

        $this->assertSame(1, View::query()->where('real_object_id', $article->id)->count());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createArticle(array $attributes = []): Article
    {
        return Article::factory()->byUser($this->author)->create(array_merge([
            'status' => ArticleStatus::APPROVED,
            'publicity' => PublicityStatus::PUBLIC,
        ], $attributes));
    }

    private function service(): ArticleServiceInterface
    {
        return app(ArticleServiceInterface::class);
    }
}

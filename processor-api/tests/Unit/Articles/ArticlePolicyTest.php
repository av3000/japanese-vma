<?php

declare(strict_types=1);

namespace Tests\Unit\Articles;

use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Articles\Models\Article;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArticlePolicyTest extends TestCase
{
    public function test_processed_and_approved_public_articles_are_visible_to_anonymous_viewers(): void
    {
        $policy = new ArticlePolicy;

        self::assertTrue($policy->canView(null, $this->article(PublicityStatus::PUBLIC, 10, ArticleStatus::PROCESSED)));
        self::assertTrue($policy->canView(null, $this->article(PublicityStatus::PUBLIC, 10, ArticleStatus::APPROVED)));
    }

    #[DataProvider('unmoderatedStatuses')]
    public function test_unmoderated_public_articles_are_hidden_from_everyone_but_the_author_and_admins(ArticleStatus $status): void
    {
        $policy = new ArticlePolicy;
        $article = $this->article(PublicityStatus::PUBLIC, 10, $status);

        self::assertFalse($policy->canView(null, $article));
        self::assertFalse($policy->canView($this->authenticatedUser(20), $article));
        self::assertTrue($policy->canView($this->authenticatedUser(10), $article));
        self::assertTrue($policy->canView($this->authenticatedUser(20, true), $article));
    }

    /**
     * @return array<string, array{ArticleStatus}>
     */
    public static function unmoderatedStatuses(): array
    {
        return [
            'pending' => [ArticleStatus::PENDING],
            'reviewing' => [ArticleStatus::REVIEWING],
            'rejected' => [ArticleStatus::REJECTED],
        ];
    }

    public function test_private_articles_are_visible_to_the_owner_and_admin_only(): void
    {
        $policy = new ArticlePolicy;
        $article = $this->article(PublicityStatus::PRIVATE, 10);

        self::assertTrue($policy->canView($this->authenticatedUser(10), $article));
        self::assertTrue($policy->canView($this->authenticatedUser(20, true), $article));
        self::assertFalse($policy->canView($this->authenticatedUser(20), $article));
        self::assertFalse($policy->canView(null, $article));
    }

    public function test_only_the_owner_or_admin_can_update_and_delete(): void
    {
        $policy = new ArticlePolicy;
        $article = $this->article(PublicityStatus::PRIVATE, 10);

        self::assertTrue($policy->canUpdate($this->authenticatedUser(10), $article));
        self::assertTrue($policy->canDelete($this->authenticatedUser(20, true), $article));
        self::assertFalse($policy->canUpdate($this->authenticatedUser(20), $article));
        self::assertFalse($policy->canDelete(null, $article));
    }

    private function authenticatedUser(int $id, bool $isAdmin = false): AuthenticatedUser
    {
        return new AuthenticatedUser(
            UserId::from($id),
            EntityId::generate(),
            UserName::from('Test User'),
            $isAdmin,
        );
    }

    private function article(PublicityStatus $publicity, int $authorId, ArticleStatus $status = ArticleStatus::APPROVED): Article
    {
        $article = $this->createMock(Article::class);
        $article->method('getPublicity')->willReturn($publicity);
        $article->method('getStatus')->willReturn($status);
        $article->method('getAuthorId')->willReturn(UserId::from($authorId));

        return $article;
    }
}

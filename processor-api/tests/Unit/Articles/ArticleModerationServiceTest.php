<?php

declare(strict_types=1);

namespace Tests\Unit\Articles;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Articles\Actions\Deletion\DeleteArticleAction;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticleModerationPolicy;
use App\Application\Articles\Services\ArticleModerationService;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Shared\Enums\HttpStatus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The `checkRole:admin` middleware rejects non-admins before the controller runs,
 * so the service-level policy guard is unreachable over HTTP. These tests cover it
 * directly to keep the second line of defence honest.
 */
final class ArticleModerationServiceTest extends TestCase
{
    public function test_non_admin_cannot_read_the_moderation_queue(): void
    {
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->expects(self::never())->method('findModerationQueue');

        $result = $this->service($repository)->getPendingArticles(
            Pagination::default(),
            $this->authenticatedUser(isAdmin: false),
        );

        self::assertTrue($result->isFailure());
        self::assertSame('Articles.ModerationAccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }

    public function test_non_admin_cannot_update_a_status(): void
    {
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->expects(self::never())->method('updateStatus');

        $result = $this->service($repository)->updateStatus(
            EntityId::generate(),
            ArticleStatus::APPROVED,
            $this->authenticatedUser(isAdmin: false),
        );

        self::assertTrue($result->isFailure());
        self::assertSame('Articles.ModerationAccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }

    public function test_admin_status_update_returns_not_found_for_an_unknown_uuid(): void
    {
        $uuid = EntityId::generate();
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findByPublicUid')
            ->willReturn(null);
        $repository->expects(self::never())->method('updateStatus');

        $result = $this->service($repository)->updateStatus(
            $uuid,
            ArticleStatus::APPROVED,
            $this->authenticatedUser(isAdmin: true),
        );

        self::assertTrue($result->isFailure());
        self::assertSame('Articles.NotFound', $result->getError()->code);
        self::assertSame(HttpStatus::NOT_FOUND, $result->getError()->toHttpStatus());
    }

    public function test_repository_failures_do_not_leak_the_exception_message(): void
    {
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->method('findByPublicUid')
            ->willThrowException(new \RuntimeException('SQLSTATE[42P01]: relation "articles" does not exist'));

        $result = $this->service($repository)->updateStatus(
            EntityId::generate(),
            ArticleStatus::APPROVED,
            $this->authenticatedUser(isAdmin: true),
        );

        self::assertTrue($result->isFailure());
        self::assertSame('Articles.ModerationStatusUpdateFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        self::assertStringNotContainsString('SQLSTATE', (string) $result->getError()->errorMessage);
        self::assertStringNotContainsString('SQLSTATE', (string) $result->getError()->detail);
    }

    public function test_a_failing_publicity_change_is_a_logged_typed_failure(): void
    {
        Log::spy();
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->method('findByPublicUid')->willThrowException(new \RuntimeException('connection lost'));

        $result = $this->service($repository)->setPublicity(
            EntityId::generate(),
            PublicityStatus::PRIVATE,
            $this->authenticatedUser(isAdmin: true),
        );

        self::assertSame('Articles.PublicityUpdateFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_failing_lookup_during_removal_is_a_logged_deletion_failure(): void
    {
        Log::spy();
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->method('findByPublicUid')->willThrowException(new \RuntimeException('connection lost'));

        $result = $this->service($repository)->remove(EntityId::generate(), $this->authenticatedUser(isAdmin: true));

        self::assertSame('Articles.DeletionFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        Log::shouldHaveReceived('error')->once();
    }

    private function service(ArticleRepositoryInterface $repository): ArticleModerationService
    {
        return new ArticleModerationService(
            $repository,
            new ArticleModerationPolicy,
            $this->createMock(HashtagServiceInterface::class),
            $this->createMock(ActivityRecorderInterface::class),
            $this->createMock(DeleteArticleAction::class),
        );
    }

    private function authenticatedUser(bool $isAdmin): AuthenticatedUser
    {
        return new AuthenticatedUser(
            UserId::from(10),
            EntityId::generate(),
            UserName::from('Test User'),
            $isAdmin,
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Articles;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Articles\Actions\Deletion\DeleteArticleAction;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticleModerationPolicy;
use App\Application\Articles\Services\ArticleModerationService;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Articles\Models\Article;
use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Shared\Enums\HttpStatus;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
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

    /**
     * @return array<string, array{ProcessingStatus|null}>
     */
    public static function unfinishedProcessing(): array
    {
        return [
            'no row' => [null],
            'pending' => [ProcessingStatus::PENDING],
            'processing' => [ProcessingStatus::PROCESSING],
            'failed' => [ProcessingStatus::FAILED],
            'superseded' => [ProcessingStatus::SUPERSEDED],
        ];
    }

    #[DataProvider('unfinishedProcessing')]
    public function test_approval_without_completed_processing_needs_the_override(?ProcessingStatus $processing): void
    {
        $repository = $this->repositoryWith(ArticleStatus::PENDING);
        $repository->expects(self::never())->method('updateStatus');

        $result = $this->service($repository, processing: $processing)->updateStatus(
            EntityId::generate(),
            ArticleStatus::APPROVED,
            $this->authenticatedUser(isAdmin: true),
        );

        self::assertSame('Articles.ApprovalNeedsFinishedProcessing', $result->getError()->code);
        self::assertSame(HttpStatus::CONFLICT, $result->getError()->toHttpStatus());
    }

    #[DataProvider('unfinishedProcessing')]
    public function test_the_override_approves_and_is_recorded(?ProcessingStatus $processing): void
    {
        $repository = $this->repositoryWith(ArticleStatus::PENDING);
        $repository->expects(self::once())->method('updateStatus')->willReturn(ArticleStatus::APPROVED);
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::once())
            ->method('record')
            ->with(self::callback(static fn (ActivityEntry $entry): bool => $entry->context === [
                'reason' => 'checked by hand',
                'approved_despite_processing' => $processing->value ?? 'none',
            ]));

        $result = $this->service($repository, processing: $processing, recorder: $recorder)->updateStatus(
            EntityId::generate(),
            ArticleStatus::APPROVED,
            $this->authenticatedUser(isAdmin: true),
            'checked by hand',
            approveUnfinishedProcessing: true,
        );

        self::assertTrue($result->isSuccess());
    }

    public function test_completed_processing_needs_no_override_and_records_none(): void
    {
        $repository = $this->repositoryWith(ArticleStatus::PENDING);
        $repository->expects(self::once())->method('updateStatus')->willReturn(ArticleStatus::APPROVED);
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::once())
            ->method('record')
            ->with(self::callback(static fn (ActivityEntry $entry): bool => $entry->context === []));

        $result = $this->service($repository, processing: ProcessingStatus::COMPLETED, recorder: $recorder)->updateStatus(
            EntityId::generate(),
            ArticleStatus::APPROVED,
            $this->authenticatedUser(isAdmin: true),
            approveUnfinishedProcessing: true,
        );

        self::assertTrue($result->isSuccess());
    }

    public function test_other_statuses_ignore_processing(): void
    {
        $repository = $this->repositoryWith(ArticleStatus::PENDING);
        $repository->expects(self::once())->method('updateStatus')->willReturn(ArticleStatus::REJECTED);
        $states = $this->createMock(ArticleProcessingStateReaderInterface::class);
        $states->expects(self::never())->method('currentState');

        $result = (new ArticleModerationService(
            $repository,
            new ArticleModerationPolicy,
            $this->createMock(HashtagServiceInterface::class),
            $this->createMock(ActivityRecorderInterface::class),
            $this->createMock(DeleteArticleAction::class),
            $states,
        ))->updateStatus(EntityId::generate(), ArticleStatus::REJECTED, $this->authenticatedUser(isAdmin: true));

        self::assertTrue($result->isSuccess());
    }

    private function repositoryWith(ArticleStatus $current): ArticleRepositoryInterface&MockObject
    {
        $article = $this->createMock(Article::class);
        $article->method('getStatus')->willReturn($current);
        $article->method('getTitleJp')->willReturn(new ArticleTitle('記事'));

        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->method('findByPublicUid')->willReturn($article);

        return $repository;
    }

    private function service(
        ArticleRepositoryInterface $repository,
        ?ProcessingStatus $processing = null,
        ?ActivityRecorderInterface $recorder = null,
    ): ArticleModerationService {
        $states = $this->createMock(ArticleProcessingStateReaderInterface::class);
        $states->method('currentState')->willReturn($processing === null ? null : new ProcessingStateDTO(
            id: 1,
            entityType: ProcessingEntityType::Article,
            entityId: 'article',
            taskType: ProcessingTaskType::ArticleContentProcessing->value,
            status: $processing,
            attempt: 1,
            sequence: 1,
            maxAttempts: 3,
            contentVersion: 1,
            metadata: null,
            errorCode: null,
            errorMessage: null,
            startedAt: null,
            finishedAt: null,
            createdAt: null,
            updatedAt: null,
        ));

        return new ArticleModerationService(
            $repository,
            new ArticleModerationPolicy,
            $this->createMock(HashtagServiceInterface::class),
            $recorder ?? $this->createMock(ActivityRecorderInterface::class),
            $this->createMock(DeleteArticleAction::class),
            $states,
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

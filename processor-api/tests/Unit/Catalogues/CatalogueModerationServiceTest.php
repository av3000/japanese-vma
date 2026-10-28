<?php

declare(strict_types=1);

namespace Tests\Unit\Catalogues;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Actions\DeleteCatalogueAction;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CatalogueModerationPolicy;
use App\Application\Catalogues\Services\CatalogueModerationService;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Services\TemplateTypeClassifier;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Shared\Enums\HttpStatus;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * Authorize first, then load, then write inside one guarded transaction: a non-moderator learns
 * nothing about which uuids exist, and a database failure is a typed, logged 500.
 */
final class CatalogueModerationServiceTest extends TestCase
{
    public function test_a_non_moderator_is_refused_before_the_catalogue_is_looked_up(): void
    {
        $catalogues = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogues->expects(self::never())->method('findByPublicUid');

        $result = $this->service($catalogues)->remove(EntityId::generate(), $this->actor(isAdmin: false));

        self::assertSame('Catalogues.AccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }

    public function test_a_failing_lookup_during_removal_is_a_logged_deletion_failure(): void
    {
        Log::spy();
        $catalogues = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogues->method('findByPublicUid')->willThrowException(new RuntimeException('connection lost'));

        $result = $this->service($catalogues)->remove(EntityId::generate(), $this->actor(isAdmin: true));

        self::assertSame('Catalogues.DeletionFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_failing_publicity_change_is_a_logged_typed_failure(): void
    {
        Log::spy();
        $catalogues = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogues->method('findByPublicUid')->willThrowException(new RuntimeException('connection lost'));

        $result = $this->service($catalogues)->setPublicity(EntityId::generate(), PublicityStatus::PRIVATE, $this->actor(isAdmin: true));

        self::assertSame('Catalogues.PublicityUpdateFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        self::assertStringNotContainsString('connection lost', (string) $result->getError()->detail);
        Log::shouldHaveReceived('error')->once();
    }

    private function service(CatalogueRepositoryInterface $catalogues): CatalogueModerationService
    {
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        return new CatalogueModerationService(
            $catalogues,
            $this->createMock(CatalogueItemRepositoryInterface::class),
            new CatalogueModerationPolicy(new TemplateTypeClassifier),
            $this->createMock(DeleteCatalogueAction::class),
            $recorder,
        );
    }

    private function actor(bool $isAdmin): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from(10), EntityId::generate(), UserName::from('Moderator'), $isAdmin);
    }
}

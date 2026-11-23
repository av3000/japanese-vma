<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Actions\DeleteCatalogueAction;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CatalogueModerationPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Catalogues\Errors\CatalogueErrors;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogueModerationService implements CatalogueModerationServiceInterface
{
    public function __construct(
        private readonly CatalogueRepositoryInterface $catalogueRepository,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly CatalogueModerationPolicy $moderationPolicy,
        private readonly DeleteCatalogueAction $deleteCatalogue,
        private readonly ActivityRecorderInterface $activityRecorder,
    ) {
    }

    public function setPublicity(EntityId $uuid, PublicityStatus $publicity, AuthenticatedUser $authenticatedUser): Result
    {
        if (! $this->moderationPolicy->canModerate($authenticatedUser)) {
            return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
        }

        try {
            $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

            if (! $catalogue) {
                return Result::failure(CatalogueErrors::notFound($uuid->value()));
            }

            $previous = $catalogue->getPublicity();

            if ($previous === $publicity) {
                return Result::success($publicity);
            }

            DB::transaction(function () use ($catalogue, $publicity, $previous, $authenticatedUser): void {
                $this->catalogueRepository->updatePublicity($catalogue->getIdValue(), $publicity);

                $this->activityRecorder->record(ActivityEntries::by(
                    $authenticatedUser,
                    ActivityAction::CataloguePublicityChanged,
                    ActivitySubjectType::Catalogue,
                    $catalogue->getUid()->value(),
                    $catalogue->getTitle()->value,
                    before: ['publicity' => $previous->label()],
                    after: ['publicity' => $publicity->label()],
                ));
            });
        } catch (Throwable $exception) {
            Log::error('Catalogue moderation publicity update failed', [
                'catalogue_uuid' => $uuid->value(),
                'user_id' => $authenticatedUser->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::publicityUpdateFailed());
        }

        return Result::success($publicity);
    }

    /**
     * Refused before the lookup for anyone who is not staff, so a non-moderator cannot tell an
     * existing uuid from a missing one. The Known-list rule needs the record and is checked after.
     */
    public function remove(EntityId $uuid, AuthenticatedUser $authenticatedUser): Result
    {
        if (! $this->moderationPolicy->canModerate($authenticatedUser)) {
            return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
        }

        try {
            $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

            if (! $catalogue) {
                return Result::failure(CatalogueErrors::notFound($uuid->value()));
            }

            if (! $this->moderationPolicy->canRemove($authenticatedUser, $catalogue)) {
                return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
            }

            DB::transaction(function () use ($catalogue, $authenticatedUser): void {
                $catalogueId = $catalogue->getIdValue();

                $this->activityRecorder->record(ActivityEntries::by(
                    $authenticatedUser,
                    ActivityAction::CatalogueDeleted,
                    ActivitySubjectType::Catalogue,
                    $catalogue->getUid()->value(),
                    $catalogue->getTitle()->value,
                    before: [
                        'title' => $catalogue->getTitle()->value,
                        'type' => $catalogue->getType()->label(),
                        'publicity' => $catalogue->getPublicity()->label(),
                        'owner' => $catalogue->getOwnerName()->value(),
                    ],
                    context: ['items' => $this->catalogueItemRepository->countItemsByCatalogueIds([$catalogueId])[$catalogueId] ?? 0],
                ));

                $this->deleteCatalogue->execute($catalogueId);
            });
        } catch (Throwable $exception) {
            Log::error('Catalogue moderation removal failed', [
                'catalogue_uuid' => $uuid->value(),
                'user_id' => $authenticatedUser->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::deletionFailed());
        }

        return Result::success();
    }
}

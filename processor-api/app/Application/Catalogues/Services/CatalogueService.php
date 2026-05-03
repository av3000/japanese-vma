<?php

namespace App\Application\Catalogues\Services;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueCriteriaDTO;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Catalogues\Errors\CatalogueErrors;
use App\Domain\Catalogues\Factories\CatalogueFactory;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Catalogues\ValueObjects\CatalogueSortCriteria;
use App\Domain\Catalogues\ValueObjects\CatalogueTitle;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CatalogueService implements CatalogueServiceInterface
{
    public function __construct(
        private readonly CatalogueRepositoryInterface $catalogueRepository,
        private readonly CataloguePolicy $cataloguePolicy,
        private readonly IncrementViewAction $incrementViewAction,
        private readonly CatalogueItemService $catalogueItemService,
        private readonly HashtagServiceInterface $hashtagService,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly HashtagRepositoryInterface $hashtagRepository,
        private readonly ViewRepositoryInterface $viewRepository,
        private readonly LikeRepositoryInterface $likeRepository,
        private readonly DownloadRepositoryInterface $downloadRepository,
        private readonly CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function createCatalogue(CatalogueCreateDTO $dto, User $user): Result
    {
        try {
            $catalogue = DB::transaction(function () use ($dto, $user) {
                $domainCatalogue = CatalogueFactory::createFromDTO(
                    $dto,
                    new UserId($user->id),
                    new UserName($user->name),
                    EntityId::from($user->uuid),
                );

                $createdCatalogue = $this->catalogueRepository->create($domainCatalogue);

                if ($dto->hashtags && ! empty($dto->hashtags)) {
                    $hashtagResult = $this->hashtagService->createTagsForEntity(
                        $createdCatalogue->getIdValue(),
                        ObjectTemplateType::LIST,
                        $dto->hashtags,
                        $user->id
                    );

                    if ($hashtagResult->isFailure()) {
                        throw new \Exception($hashtagResult->getError()->description);
                    }
                }

                return $createdCatalogue;
            });

            return Result::success($catalogue);
        } catch (\Exception $e) {
            Log::error('Catalogue creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::creationFailed());
        }
    }

    public function getCatalogueList(CatalogueListDTO $dto, ?User $user = null): Catalogues
    {
        $requestedOwnerUid = $dto->owner_uid;
        $canIndexPrivateCatalogues = $this->cataloguePolicy->canIndexPrivateCatalogues($user, $requestedOwnerUid);
        $publicOnly = $dto->public_only;

        if ($requestedOwnerUid === null) {
            $publicOnly = true;
        } elseif (! $publicOnly && ! $canIndexPrivateCatalogues) {
            $publicOnly = true;
        }

        $criteria = new CatalogueCriteriaDTO(
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            sort: CatalogueSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir),
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            ownerUid: $requestedOwnerUid,
            type: $dto->type,
            publicOnly: $publicOnly,
            customOnly: $requestedOwnerUid === null ? true : $dto->custom_only
        );

        return $this->catalogueRepository->findByCriteria($criteria);
    }

    public function getIdByUuid(EntityId $uuid): ?int
    {
        return $this->catalogueRepository->getIdByUuid($uuid);
    }

    public function getCatalogue(EntityId $uuid, ?User $user = null): Result
    {
        $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

        if (! $catalogue) {
            return Result::failure(CatalogueErrors::notFound($uuid->value()));
        }

        if (! $this->cataloguePolicy->canView($user, $catalogue)) {
            return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
        }

        $viewer = new Viewer($user?->id, request()->ip());
        $this->trackView($catalogue->getIdValue(), ObjectTemplateType::LIST, $viewer);

        $items = $this->catalogueItemService->getItems($catalogue);

        return Result::success(
            new CatalogueDetailDTO(
                catalogue: $catalogue,
                items: $items,
                itemsCount: count($items)
            )
        );
    }

    public function updateCatalogue(EntityId $uuid, CatalogueUpdateDTO $dto, User $user): Result
    {
        try {
            $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

            if (! $catalogue) {
                return Result::failure(CatalogueErrors::notFound($uuid->value()));
            }

            if (! $this->cataloguePolicy->canUpdate($user, $catalogue)) {
                return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
            }

            $updatedCatalogue = DB::transaction(function () use ($catalogue, $dto, $user) {
                $updatedCatalogue = $this->applyUpdates($catalogue, $dto);

                $this->catalogueRepository->update($updatedCatalogue);

                if ($dto->hashtagsPresent) {
                    $hashtagResult = $this->hashtagService->syncTagsForEntity(
                        $catalogue->getIdValue(),
                        ObjectTemplateType::LIST,
                        $dto->hashtags ?? [],
                        $user->id
                    );

                    if ($hashtagResult->isFailure()) {
                        throw new \Exception($hashtagResult->getError()->description);
                    }
                }

                return $updatedCatalogue;
            });

            return Result::success($updatedCatalogue);
        } catch (\Exception $e) {
            Log::error('Catalogue update failed', [
                'user_id' => $user->id,
                'catalogue_uuid' => $uuid->value(),
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::updateFailed($e->getMessage()));
        }
    }

    public function addItemToCatalogue(EntityId $uuid, int $itemId, User $user): Result
    {
        try {
            $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

            if (! $catalogue) {
                return Result::failure(CatalogueErrors::notFound($uuid->value()));
            }

            if (! $this->cataloguePolicy->canUpdate($user, $catalogue)) {
                return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
            }

            if (! $this->catalogueItemService->isValidItemForCatalogue($catalogue, $itemId)) {
                return Result::failure(CatalogueErrors::invalidItemForType($uuid->value(), $itemId));
            }

            if ($this->catalogueItemRepository->containsItem($catalogue->getIdValue(), $itemId)) {
                return Result::failure(CatalogueErrors::duplicateItem($uuid->value(), $itemId));
            }

            DB::transaction(function () use ($catalogue, $itemId) {
                $this->catalogueItemService->addItem($catalogue, $itemId);
            });

            return Result::success([
                'catalogue_uuid' => $catalogue->getUid()->value(),
                'item_id' => $itemId,
            ]);
        } catch (\Exception $e) {
            Log::error('Catalogue item add failed', [
                'catalogue_uuid' => $uuid->value(),
                'item_id' => $itemId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::addItemFailed());
        }
    }

    public function removeItemFromCatalogue(EntityId $uuid, int $itemId, User $user): Result
    {
        try {
            $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

            if (! $catalogue) {
                return Result::failure(CatalogueErrors::notFound($uuid->value()));
            }

            if (! $this->cataloguePolicy->canUpdate($user, $catalogue)) {
                return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
            }

            if (! $this->catalogueItemRepository->containsItem($catalogue->getIdValue(), $itemId)) {
                return Result::failure(CatalogueErrors::itemNotFound($uuid->value(), $itemId));
            }

            $wasRemoved = DB::transaction(function () use ($catalogue, $itemId) {
                return $this->catalogueItemService->removeItem($catalogue, $itemId);
            });

            if (! $wasRemoved) {
                return Result::failure(CatalogueErrors::removeItemFailed());
            }

            return Result::success();
        } catch (\Exception $e) {
            Log::error('Catalogue item removal failed', [
                'catalogue_uuid' => $uuid->value(),
                'item_id' => $itemId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::removeItemFailed());
        }
    }

    public function deleteCatalogue(EntityId $uuid, User $user): Result
    {
        try {
            $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

            if (! $catalogue) {
                return Result::failure(CatalogueErrors::notFound($uuid->value()));
            }

            if (! $this->cataloguePolicy->canDelete($user, $catalogue)) {
                return Result::failure(CatalogueErrors::accessDenied($uuid->value()));
            }

            DB::transaction(function () use ($catalogue) {
                $catalogueId = $catalogue->getIdValue();
                $templateId = ObjectTemplateType::LIST->getLegacyId();

                $this->catalogueItemRepository->deleteByCatalogueId($catalogueId);
                $this->viewRepository->deleteByEntity($catalogueId, $templateId);
                $this->downloadRepository->deleteByEntity($catalogueId, $templateId);
                $this->likeRepository->deleteByEntity($catalogueId, $templateId);
                $this->commentRepository->deleteByEntity($catalogueId, $templateId);
                $this->hashtagRepository->deleteByEntity($catalogueId, $templateId);
                $this->catalogueRepository->deleteById($catalogueId);
            });

            return Result::success();
        } catch (\Exception $e) {
            Log::error('Catalogue deletion failed', [
                'catalogue_uuid' => $uuid->value(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CatalogueErrors::deletionFailed());
        }
    }

    private function trackView(int $id, ObjectTemplateType $objectTemplateType, Viewer $viewer): void
    {
        try {
            $this->incrementViewAction->execute($id, $objectTemplateType, $viewer);
        } catch (\Exception $e) {
            Log::error("Failed to increment view for catalogue {$id}: ".$e->getMessage());
        }
    }

    private function applyUpdates(Catalogue $catalogue, CatalogueUpdateDTO $dto): Catalogue
    {
        return new Catalogue(
            $catalogue->getIdValue(),
            $catalogue->getUid(),
            $dto->type ?? $catalogue->getType(),
            $dto->title !== null
                ? CatalogueTitle::fromInput($dto->title)
                : $catalogue->getTitle(),
            $catalogue->getDescription(),
            $dto->publicity !== null
                ? ($dto->publicity ? PublicityStatus::PUBLIC : PublicityStatus::PRIVATE)
                : $catalogue->getPublicity(),
            $catalogue->getOwnerId(),
            $catalogue->getOwnerName(),
            $catalogue->getOwnerUuid(),
            $catalogue->getCreatedAt(),
            now()->toDateTimeImmutable(),
        );
    }
}

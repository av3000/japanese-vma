<?php

namespace App\Application\Catalogues\Services;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Application\Engagement\Actions\IncrementViewAction;
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
    ) {}

    public function createCatalogue(CatalogueCreateDTO $dto, User $user, Viewer $viewer): Result
    {
        try {
            $catalogue = DB::transaction(function () use ($dto, $user, $viewer) {
                $domainCatalogue = CatalogueFactory::createFromDTO(
                    $dto,
                    new UserId($user->id),
                    new UserName($user->name),
                    EntityId::from($user->uuid),
                );

                $createdCatalogue = $this->catalogueRepository->create($domainCatalogue);

                if (! empty($dto->hashtags)) {
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

                $this->trackView(
                    $createdCatalogue->getIdValue(),
                    ObjectTemplateType::LIST,
                    $viewer
                );

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

    private function trackView(int $id, ObjectTemplateType $objectTemplateType, Viewer $viewer): void
    {
        try {
            $this->incrementViewAction->execute($id, $objectTemplateType, $viewer);
        } catch (\Exception $e) {
            Log::error("Failed to increment view for catalogue {$id}: " . $e->getMessage());
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
            new \DateTimeImmutable(),
        );
    }
}

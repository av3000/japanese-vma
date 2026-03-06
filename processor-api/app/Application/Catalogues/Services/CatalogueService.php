<?php

namespace App\Application\Catalogues\Services;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Domain\Catalogues\DTOs\{CatalogueListDTO, CatalogueCriteriaDTO, CatalogueDetailDTO};
use App\Domain\Catalogues\Errors\CatalogueErrors;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Catalogues\ValueObjects\CatalogueSortCriteria;
use App\Domain\Shared\ValueObjects\{EntityId, Pagination, SearchTerm, Viewer};
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Shared\Results\Result;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Log;

class CatalogueService implements CatalogueServiceInterface
{
    public function __construct(
        private readonly CatalogueRepositoryInterface $catalogueRepository,
        private readonly CataloguePolicy $cataloguePolicy,
        private readonly IncrementViewAction $incrementViewAction,
        private readonly CatalogueItemService $catalogueItemService,
    ) {}

    public function getCatalogueList(CatalogueListDTO $dto, ?User $user = null): Catalogues
    {
        $criteria = new CatalogueCriteriaDTO(
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            sort: CatalogueSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir),
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            publicOnly: true,
            customOnly: true
        );

        return $this->catalogueRepository->findByCriteria($criteria);
    }

    public function getCatalogue(EntityId $uuid, ?User $user = null): Result
    {
        $catalogue = $this->catalogueRepository->findByPublicUid($uuid);

        if (!$catalogue) {
            return Result::failure(CatalogueErrors::notFound($uuid->value()));
        }

        if (!$this->cataloguePolicy->canView($user, $catalogue)) {
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

    private function trackView(int $id, ObjectTemplateType $objectTemplateType, Viewer $viewer): void
    {
        try {
            $this->incrementViewAction->execute($id, $objectTemplateType, $viewer);
        } catch (\Exception $e) {
            Log::error("Failed to increment view for catalogue {$id}: " . $e->getMessage());
        }
    }
}

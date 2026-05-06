<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Domain\Catalogues\DTOs\CatalogueCriteriaDTO;
use App\Domain\Catalogues\Enums\CatalogueSortField;
use App\Domain\Catalogues\Models\Catalogue as DomainCatalogue;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Shared\Enums\CatalogueType;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Catalogue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogueRepository implements CatalogueRepositoryInterface
{
    public function __construct(
        private readonly CatalogueMapper $catalogueMapper
    ) {
    }

    public function createDefaultCataloguesForUser(UserId $userId): void
    {
        foreach (CatalogueType::cases() as $listType) {
            $this->createDefaultCatalogue(
                userId: $userId,
                type: $listType,
                title: $listType->title(),
                description: $listType->description(),
                publicity: false
            );
        }
    }

    public function create(DomainCatalogue $catalogue): DomainCatalogue
    {
        $entity = Catalogue::create($this->catalogueMapper->mapToEntity($catalogue));
        $entity->load('user');

        return $this->catalogueMapper->mapToDomain($entity);
    }

    public function update(DomainCatalogue $catalogue): void
    {
        $entity = Catalogue::with('user')
            ->where('uuid', $catalogue->getUid()->value())
            ->firstOrFail();

        $this->catalogueMapper->mapToExistingEntity($catalogue, $entity);
        $entity->save();
    }

    public function deleteById(int $id): bool
    {
        $entity = Catalogue::findOrFail($id);

        return $entity->delete();
    }

    private function createDefaultCatalogue(
        UserId $userId,
        CatalogueType $type,
        string $title,
        string $description,
        bool $publicity = false
    ): void {
        Catalogue::create([
            'user_id' => $userId->value(),
            'uuid' => Str::uuid()->toString(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'type' => $type->value,
            'title' => $title,
            'description' => $description,
            'publicity' => $publicity,
        ]);
    }

    /**
     * Get integer ID from catalogue UUID.
     *
     * Performs a lightweight query returning only the ID column.
     * Useful when you need the integer ID for operations but only have the public UUID.
     *
     * @param  EntityId  $entityUuid  The catalogue's public UUID
     * @return int|null The catalogue's integer ID, or null if UUID not found
     *
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function getIdByUuid(EntityId $entityUuid): ?int
    {
        return Catalogue::where('uuid', $entityUuid->value())->value('id');
    }

    public function findByCriteria(CatalogueCriteriaDTO $criteria): Catalogues
    {
        $query = $this->buildCatalogueQuery(
            ownerUid: $criteria->ownerUid,
            publicOnly: $criteria->publicOnly,
            customOnly: $criteria->customOnly,
            type: $criteria->type,
            search: $criteria->search,
        );

        if ($criteria->sort->field === CatalogueSortField::VIEWS) {
            $templateId = ObjectTemplateType::LIST->getLegacyId();
            $query->select('customlists.*')
                ->leftJoin('views', function ($join) use ($templateId) {
                    $join->on('customlists.id', '=', 'views.real_object_id')
                        ->where('views.template_id', '=', $templateId);
                })
                ->addSelect(DB::raw('count(views.real_object_id) as viewsTotal'))
                ->groupBy('customlists.id')
                ->orderBy('viewsTotal', $criteria->sort->direction->value);
        } else {
            $query->orderBy($criteria->sort->field->value, $criteria->sort->direction->value);
        }

        $paginatedResults = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page
        );

        $domainCatalogues = $paginatedResults->getCollection()->map(function ($entity) {
            return $this->catalogueMapper->mapToDomain($entity);
        });

        $paginatedResults->setCollection($domainCatalogues);

        return Catalogues::fromEloquentPaginator($paginatedResults);
    }

    public function findOwnedForMembership(string $ownerUuid, ?SearchTerm $search = null, array $types = []): array
    {
        $query = $this->buildCatalogueQuery(
            ownerUid: $ownerUuid,
            publicOnly: false,
            customOnly: false,
            type: null,
            types: $types,
            search: $search,
        );

        $query->orderBy('created_at', 'desc');

        return $query
            ->get()
            ->map(fn (Catalogue $entity): DomainCatalogue => $this->catalogueMapper->mapToDomain($entity))
            ->all();
    }

    public function findByPublicUid(EntityId $uuid): ?DomainCatalogue
    {
        $entity = Catalogue::with(['user'])
            ->where('uuid', $uuid->value())
            ->first();

        return $entity ? $this->catalogueMapper->mapToDomain($entity) : null;
    }

    private function buildCatalogueQuery(
        ?string $ownerUid,
        bool $publicOnly,
        bool $customOnly,
        ?int $type = null,
        array $types = [],
        ?SearchTerm $search = null,
    ): Builder {
        $query = Catalogue::query()->with(['user']);

        if ($ownerUid !== null) {
            $query->whereHas('user', function (Builder $userQuery) use ($ownerUid) {
                $userQuery->where('uuid', $ownerUid);
            });
        }

        if ($publicOnly) {
            $query->where('publicity', 1);
        }

        if ($customOnly) {
            $query->where('type', '>', 4);
        }

        if ($types !== []) {
            $query->whereIn('type', $types);
        } elseif ($type !== null) {
            $query->where('type', $type);
        }

        if ($search !== null) {
            $searchValue = $search->value;
            $query->where(function (Builder $q) use ($searchValue) {
                $q->where('title', 'LIKE', '%'.$searchValue.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchValue.'%');
            });
        }

        return $query;
    }
}

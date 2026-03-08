<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Domain\Catalogues\DTOs\CatalogueCriteriaDTO;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Catalogues\Models\Catalogue as DomainCatalogue;
use App\Domain\Catalogues\Enums\CatalogueSortField;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Domain\Shared\Enums\CatalogueType;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogueRepository implements CatalogueRepositoryInterface
{
    public function __construct(
        private readonly CatalogueMapper $catalogueMapper
    ) {}

    public function createDefaultCataloguesForUser(UserId $userId): void
    {
        foreach (CatalogueType::cases() as $listType) {
            $this->create(
                userId: $userId,
                type: $listType,
                title: $listType->title(),
                description: $listType->description(),
                publicity: false
            );
        }
    }

    public function create(
        UserId $userId,
        CatalogueType $type,
        string $title,
        string $description,
        bool $publicity = false
    ): void {
        Catalogue::create([
            'user_id' => $userId->value(),
            'uuid' => Str::uuid()->toString(),
            'type' => $type->value,
            'title' => $title,
            'description' => $description,
            'publicity' => $publicity,
        ]);
    }

    public function findByCriteria(CatalogueCriteriaDTO $criteria): Catalogues
    {
        $query = Catalogue::query()->with(['user']);

        if ($criteria->ownerUid !== null) {
            $query->whereHas('user', function (Builder $userQuery) use ($criteria) {
                $userQuery->where('uuid', $criteria->ownerUid);
            });
        }

        if ($criteria->publicOnly) {
            $query->where('publicity', 1);
        }

        if ($criteria->customOnly) {
            $query->where('type', '>', 4);
        }

        if ($criteria->type !== null) {
            $query->where('type', $criteria->type);
        }

        if ($criteria->search !== null) {
            $searchValue = $criteria->search->value;
            $query->where(function (Builder $q) use ($searchValue) {
                $q->where('title', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('description', 'LIKE', '%' . $searchValue . '%');
            });
        }

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

    public function findByPublicUid(EntityId $uuid): ?DomainCatalogue
    {
        $entity = Catalogue::with(['user'])
            ->where('uuid', $uuid->value())
            ->first();

        return $entity ? $this->catalogueMapper->mapToDomain($entity) : null;
    }
}

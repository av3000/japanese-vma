<?php

namespace App\Http\v1\Catalogues\Controllers;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\Models\CatalogueStats;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\Controllers\Controller;
use App\Http\v1\Catalogues\Requests\IndexCatalogueRequest;
use App\Http\v1\Catalogues\Resources\CatalogueDetailResource;
use App\Http\v1\Catalogues\Resources\CatalogueResource;
use App\Shared\Http\TypedResults;
use Illuminate\Http\JsonResponse;

class CatalogueController extends Controller
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly LoadEntityStatsAction $loadStats,
        private readonly HashtagServiceInterface $hashtagService,
    ) {}

    public function index(IndexCatalogueRequest $request): JsonResponse
    {
        $catalogueDTO = CatalogueListDTO::fromRequest($request->validated());

        $paginatedCatalogues = $this->catalogueService->getCatalogueList($catalogueDTO, auth('api')->user());

        $catalogueIds = array_map(fn($catalogue) => $catalogue->getIdValue(), $paginatedCatalogues->getItems());

        $itemsCountMap = $this->catalogueItemRepository->countItemsByCatalogueIds($catalogueIds);

        $statsMap = [];
        if ($catalogueDTO->include_stats_counts) {
            $statsData = $this->loadStats->batchLoadStatsById(ObjectTemplateType::LIST->getLegacyId(), $catalogueIds);
            foreach ($catalogueIds as $id) {
                $stats = $statsData[$id] ?? [
                    'likes' => 0,
                    'downloads' => 0,
                    'views' => 0,
                    'comments' => 0,
                ];
                $statsMap[$id] = new CatalogueStats(
                    $stats['likes'],
                    $stats['downloads'],
                    $stats['views'],
                    $stats['comments']
                );
            }
        }

        $hashtagsMap = [];
        if ($catalogueDTO->include_hashtags) {
            $hashtagsMap = $this->hashtagService->getBatchHashtags(
                $catalogueIds,
                ObjectTemplateType::LIST
            );
        }

        $resources = [];
        foreach ($paginatedCatalogues->getItems() as $catalogue) {
            $id = $catalogue->getIdValue();
            $resources[] = new CatalogueResource(
                $catalogue,
                $statsMap[$id] ?? null,
                $hashtagsMap[$id] ?? [],
                $itemsCountMap[$id] ?? 0
            );
        }

        $paginator = $paginatedCatalogues->getPaginator();

        return TypedResults::ok([
            'items' => $resources,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $catalogueUid = EntityId::from($uuid);
        $result = $this->catalogueService->getCatalogue($catalogueUid, auth('api')->user());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var CatalogueDetailDTO $detail */
        $detail = $result->getData();
        $catalogue = $detail->catalogue;

        $statsData = $this->loadStats->batchLoadStatsById(
            ObjectTemplateType::LIST->getLegacyId(),
            [$catalogue->getIdValue()]
        );

        $statsRow = $statsData[$catalogue->getIdValue()] ?? [
            'likes' => 0,
            'downloads' => 0,
            'views' => 0,
            'comments' => 0,
        ];

        $stats = new CatalogueStats(
            $statsRow['likes'],
            $statsRow['downloads'],
            $statsRow['views'],
            $statsRow['comments']
        );

        $hashtags = $this->hashtagService->getHashtags(
            $catalogue->getIdValue(),
            ObjectTemplateType::LIST
        );

        return TypedResults::ok(
            new CatalogueDetailResource(
                $catalogue,
                $detail->items,
                $stats,
                $hashtags,
                $detail->itemsCount
            )
        );
    }
}

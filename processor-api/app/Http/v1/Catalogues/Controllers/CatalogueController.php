<?php

namespace App\Http\v1\Catalogues\Controllers;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CataloguePickerItemDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Catalogues\Models\CatalogueStats;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\Controllers\Controller;
use App\Http\v1\Catalogues\Requests\IndexCatalogueRequest;
use App\Http\v1\Catalogues\Requests\IndexCataloguesForItemRequest;
use App\Http\v1\Catalogues\Requests\StoreCatalogueItemRequest;
use App\Http\v1\Catalogues\Requests\StoreCatalogueRequest;
use App\Http\v1\Catalogues\Requests\UpdateCatalogueRequest;
use App\Http\v1\Catalogues\Resources\CatalogueDetailResource;
use App\Http\v1\Catalogues\Resources\CatalogueForItemListResource;
use App\Http\v1\Catalogues\Resources\CatalogueForItemResource;
use App\Http\v1\Catalogues\Resources\CatalogueListResource;
use App\Http\v1\Catalogues\Resources\CatalogueResource;
use App\Http\v1\Shared\Resources\UuidCreatedResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CatalogueController extends Controller
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly LoadEntityStatsAction $loadStats,
        private readonly EngagementServiceInterface $engagementService,
        private readonly HashtagServiceInterface $hashtagService,
    ) {}

    /**
     * @response CatalogueListResource
     */
    #[Response(type: 'CatalogueListResource')]
    public function index(IndexCatalogueRequest $request): JsonResponse|JsonResource
    {
        $catalogueDTO = CatalogueListDTO::fromRequest($request->validated());

        $catalogueList = $this->catalogueService->getCatalogueList($catalogueDTO, auth('api')->user());

        return new CatalogueListResource($catalogueList);
    }

    /**
     * @response array{
     *     items: array<int, array{
     *         id: int,
     *         uuid: string,
     *         title: string,
     *         type: int,
     *         type_label: string,
     *         publicity: int,
     *         contains_item: bool
     *     }>
     * }
     */
    #[Response(type: 'array{items: array<int, array{id: int, uuid: string, title: string, type: int, type_label: string, publicity: int, contains_item: bool}>}')]
    public function forItem(IndexCataloguesForItemRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();
        $cataloguesForItem = $this->catalogueService->getCataloguesForItem(
            (int) $validated['item_id'],
            $validated['types'] ?? [],
            $validated['search'] ?? null,
            auth('api')->user(),
        );

        $resources = array_map(
            static fn(CataloguePickerItemDTO $item): CatalogueForItemResource => new CatalogueForItemResource(
                $item->catalogue,
                $item->containsItem,
            ),
            $cataloguesForItem->items,
        );

        return new CatalogueForItemListResource([
            'items' => $resources,
        ]);
    }

    /**
     * @response UuidCreatedResource
     */
    #[Response(201, type: 'UuidCreatedResource')]
    public function store(StoreCatalogueRequest $request): JsonResponse|JsonResource
    {
        $createDTO = CatalogueCreateDTO::fromRequest($request->validated());

        $result = $this->catalogueService->createCatalogue(
            $createDTO,
            auth('api')->user(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $catalogue = $result->getData();

        return new UuidCreatedResource([
            'uuid' => $catalogue->getUid()->value(),
        ]);
    }

    /**
     * @response array{}
     */
    #[Response(201, type: 'array{}')]
    public function addItem(string $uuid, StoreCatalogueItemRequest $request): JsonResponse
    {
        $catalogueUid = EntityId::from($uuid);
        $result = $this->catalogueService->addItemToCatalogue(
            $catalogueUid,
            (int) $request->validated('item_id'),
            auth('api')->user(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return response()->json([], 201);
    }

    /**
     * @response 204
     */
    #[Response(204)]
    public function removeItem(string $uuid, int $itemId): HttpResponse|JsonResponse
    {
        $catalogueUid = EntityId::from($uuid);
        $result = $this->catalogueService->removeItemFromCatalogue(
            $catalogueUid,
            $itemId,
            auth('api')->user(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return response()->noContent();
    }

    /**
     * @response CatalogueDetailResource
     */
    #[Response(type: 'CatalogueDetailResource')]
    public function show(string $uuid): JsonResponse|JsonResource
    {
        $catalogueUid = EntityId::from($uuid);
        $viewer = auth('api')->user();
        $detailResult = $this->catalogueService->getCatalogueDetail($catalogueUid, $viewer);

        if ($detailResult->isFailure()) {
            return TypedResults::fromError($detailResult->getError());
        }

        /** @var CatalogueDetailDTO $detail */
        $detail = $detailResult->getData();
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

        $isLikedByViewer = $this->engagementService->isEntityLikedByViewer(
            $catalogue->getIdValue(),
            ObjectTemplateType::LIST,
            $viewer !== null
        );

        return new CatalogueDetailResource(
            $catalogue,
            $detail->items,
            $stats,
            $hashtags,
            $detail->itemsCount,
            $isLikedByViewer
        );
    }

    /**
     * @response CatalogueResource
     */
    #[Response(type: 'CatalogueResource')]
    public function update(string $uuid, UpdateCatalogueRequest $request): JsonResponse|JsonResource
    {
        if (! $request->hasAnyUpdateableFields()) {
            return TypedResults::validationProblem(
                ['fields' => ['At least one field must be provided for update operation']],
                'No fields to update'
            );
        }

        $catalogueUid = EntityId::from($uuid);
        $updateDTO = CatalogueUpdateDTO::fromRequest($request->validated());
        $result = $this->catalogueService->updateCatalogue($catalogueUid, $updateDTO, auth('api')->user());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $catalogue = $result->getData();
        $hashtags = $this->hashtagService->getHashtags(
            $catalogue->getIdValue(),
            ObjectTemplateType::LIST
        );
        $itemsCountMap = $this->catalogueItemRepository->countItemsByCatalogueIds([$catalogue->getIdValue()]);
        $itemsCount = $itemsCountMap[$catalogue->getIdValue()] ?? 0;

        return new CatalogueResource(
            catalogue: $catalogue,
            hashtags: $hashtags,
            itemsCount: $itemsCount
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $catalogueUid = EntityId::from($uuid);
        $result = $this->catalogueService->deleteCatalogue($catalogueUid, auth('api')->user());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::noContent();
    }
}

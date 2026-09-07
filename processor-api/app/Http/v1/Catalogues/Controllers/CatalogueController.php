<?php

namespace App\Http\v1\Catalogues\Controllers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Catalogues\Services\CataloguePdfExportServiceInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateResultDTO;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Http\Controllers\Controller;
use App\Http\v1\Catalogues\Requests\IndexCatalogueRequest;
use App\Http\v1\Catalogues\Requests\IndexCataloguesForItemRequest;
use App\Http\v1\Catalogues\Requests\StoreCatalogueItemRequest;
use App\Http\v1\Catalogues\Requests\StoreCatalogueRequest;
use App\Http\v1\Catalogues\Requests\UpdateCatalogueRequest;
use App\Http\v1\Catalogues\Resources\CatalogueDetailResource;
use App\Http\v1\Catalogues\Resources\CatalogueListForItemResource;
use App\Http\v1\Catalogues\Resources\CatalogueListResource;
use App\Http\v1\Catalogues\Resources\CatalogueResource;
use App\Http\v1\Shared\Resources\UuidCreatedResource;
use App\Shared\Http\PdfResponseFactory;
use App\Shared\Http\TypedResults;
use App\Shared\Results\Result;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as LaravelResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CatalogueController extends Controller
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly CataloguePdfExportServiceInterface $cataloguePdfExportService,
        private readonly PdfResponseFactory $pdfResponseFactory,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * @response CatalogueListResource
     */
    #[Response(type: 'CatalogueListResource')]
    public function index(IndexCatalogueRequest $request): JsonResponse|JsonResource
    {
        $catalogueDTO = CatalogueListDTO::fromRequest($request->validated());

        $catalogueList = $this->catalogueService->getCatalogueList(
            $catalogueDTO,
            $this->currentUserProvider->currentAuthenticatedUser(),
        );

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
            $this->requiredAuthenticatedUser(),
        );

        return new CatalogueListForItemResource($cataloguesForItem);
    }

    /**
     * @response UuidCreatedResource
     */
    #[Response(201, type: 'UuidCreatedResource')]
    public function store(StoreCatalogueRequest $request): JsonResponse|JsonResource
    {
        $createDTO = CatalogueCreateDTO::fromRequest($request->validated());
        $authenticatedUser = $this->requiredAuthenticatedUser();

        $result = $this->catalogueService->createCatalogue(
            $createDTO,
            $authenticatedUser,
            new Viewer($authenticatedUser->id, (string) $request->ip()),
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
            $this->requiredAuthenticatedUser(),
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
            $this->requiredAuthenticatedUser(),
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
    public function show(string $uuid, Request $request): JsonResponse|JsonResource
    {
        $catalogueUid = EntityId::from($uuid);
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();
        $viewer = new Viewer($authenticatedUser?->id, (string) $request->ip());
        $detailResult = $this->catalogueService->getCatalogueDetail($catalogueUid, $viewer, $authenticatedUser);

        if ($detailResult->isFailure()) {
            return TypedResults::fromError($detailResult->getError());
        }

        /** @var CatalogueDetailDTO $detail */
        $detail = $detailResult->getData();

        return new CatalogueDetailResource($detail);
    }

    /**
     * @response CatalogueResource
     */
    #[Response(type: 'CatalogueResource')]
    public function update(string $uuid, UpdateCatalogueRequest $request): JsonResponse|JsonResource
    {
        $catalogueUid = EntityId::from($uuid);
        $updateDTO = CatalogueUpdateDTO::fromRequest($request->validated());
        $result = $this->catalogueService->updateCatalogue($catalogueUid, $updateDTO, $this->requiredAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var CatalogueUpdateResultDTO $updateResult */
        $updateResult = $result->getData();

        return new CatalogueResource(
            catalogue: $updateResult->catalogue,
            hashtags: $updateResult->hashtags,
            itemsCount: $updateResult->itemsCount,
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $catalogueUid = EntityId::from($uuid);
        $result = $this->catalogueService->deleteCatalogue($catalogueUid, $this->requiredAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::noContent();
    }

    public function exportKanjisPdf(string $uuid): JsonResponse|LaravelResponse
    {
        return $this->pdfResult($this->cataloguePdfExportService->exportKanjis(
            EntityId::from($uuid),
            $this->requiredAuthenticatedUser(),
        ));
    }

    public function exportWordsPdf(string $uuid): JsonResponse|LaravelResponse
    {
        return $this->pdfResult($this->cataloguePdfExportService->exportWords(
            EntityId::from($uuid),
            $this->requiredAuthenticatedUser(),
        ));
    }

    private function pdfResult(Result $result): JsonResponse|LaravelResponse
    {
        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var PdfRenderResult $pdf */
        $pdf = $result->getData();

        return $this->pdfResponseFactory->make($pdf);
    }

    private function requiredAuthenticatedUser(): AuthenticatedUser
    {
        return $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;
    }
}

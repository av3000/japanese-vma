<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiDetailServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Kanjis\Requests\IndexKanjiRequest;
use App\Http\v1\JapaneseMaterial\Kanjis\Requests\ShowKanjiRequest;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiDetailResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiListResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class KanjiController extends Controller
{
    public function __construct(
        private readonly KanjiServiceInterface $kanjiService,
        private readonly KanjiDetailServiceInterface $kanjiDetailService,
        private readonly ViewerCatalogueStateService $viewerCatalogueStateService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * @response array{
     *     items: array<int, array{
     *         id: int,
     *         uuid: string,
     *         character: string,
     *         onyomi: array<int, string>,
     *         kunyomi: array<int, string>,
     *         meanings: array<int, string>,
     *         nanori: array<int, string>,
     *         grade: string|null,
     *         stroke_count: int,
     *         jlpt: string|null,
     *         frequency: int|null,
     *         radicals: array<int, string>,
     *         radical_parts: array<int, string>,
     *         viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null
     *     }>,
     *     pagination: PaginationResource
     * }
     */
    #[Response(type: 'array{items: array<int, array{id: int, uuid: string, character: string, onyomi: array<int, string>, kunyomi: array<int, string>, meanings: array<int, string>, nanori: array<int, string>, grade: string|null, stroke_count: int, jlpt: string|null, frequency: int|null, radicals: array<int, string>, radical_parts: array<int, string>, viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null}>, pagination: PaginationResource}')]
    public function index(IndexKanjiRequest $request): JsonResponse|JsonResource
    {
        $validatedData = $request->validated();

        $articleId = isset($validatedData['article_uuid'])
            ? EntityId::from($validatedData['article_uuid'])
            : null;

        $criteria = KanjiQueryCriteria::forListing(
            page: $validatedData['page'] ?? Pagination::MIN_PAGE,
            perPage: $validatedData['per_page'] ?? Pagination::DEFAULT_PER_PAGE,
            keyword: $validatedData['keyword'] ?? null,
            character: $validatedData['character'] ?? null,
            grade: $validatedData['grade'] ?? null,
            jlpt: $validatedData['jlpt'] ?? null,
            minStrokeCount: $validatedData['min_stroke_count'] ?? null,
            maxStrokeCount: $validatedData['max_stroke_count'] ?? null,
            meanings: $this->splitCsvFilter($validatedData['meanings'] ?? null),
            onyomi: $this->splitCsvFilter($validatedData['onyomi'] ?? null),
            kunyomi: $this->splitCsvFilter($validatedData['kunyomi'] ?? null),
            radical: $validatedData['radical'] ?? null,
            limit: $validatedData['limit'] ?? null,
            offset: $validatedData['offset'] ?? null,
            articleId: $articleId,
        );

        $paginatedKanjisResult = $this->kanjiService->find($criteria);

        if ($paginatedKanjisResult->isFailure()) {
            return TypedResults::fromError($paginatedKanjisResult->getError());
        }

        $kanjiListResult = $paginatedKanjisResult->getData();
        $viewerCatalogueStates = [];
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();

        if ($request->includesViewerCatalogueState() && $authenticatedUser !== null) {
            $viewerCatalogueStates = $this->viewerCatalogueStateService->forItems(
                ownerUuid: $authenticatedUser->uuid,
                itemIds: array_map(
                    static fn ($kanji): int => $kanji->getIdValue(),
                    $kanjiListResult->items,
                ),
                savedType: SavedListType::KANJIS,
                knownType: SavedListType::KNOWNKANJIS,
            );
        }

        return new KanjiListResource($kanjiListResult, $viewerCatalogueStates);
    }

    #[Response(type: 'KanjiDetailResource')]
    public function show(string $identifier, ShowKanjiRequest $request): JsonResponse|JsonResource
    {
        $result = $this->kanjiDetailService->findByIdentifier(
            identifier: $identifier,
            includes: $request->includes(),
            authenticatedUser: $this->currentUserProvider->currentAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new KanjiDetailResource($result->getData());
    }

    /**
     * @return array<int, string>|null
     */
    private function splitCsvFilter(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            fn (string $item): bool => $item !== '',
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Controllers;

use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Words\Services\WordDetailServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Words\Requests\IndexWordRequest;
use App\Http\v1\JapaneseMaterial\Words\Requests\ShowWordRequest;
use App\Http\v1\JapaneseMaterial\Words\Resources\WordDetailResource;
use App\Http\v1\JapaneseMaterial\Words\Resources\WordListResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class WordController extends Controller
{
    public function __construct(
        private readonly WordServiceInterface $wordService,
        private readonly WordDetailServiceInterface $wordDetailService,
        private readonly ViewerCatalogueStateService $viewerCatalogueStateService,
    ) {
    }

    /**
     * @response array{
     *     items: array<int, array{
     *         id: int,
     *         uuid: string,
     *         word: string,
     *         furigana: string,
     *         jlpt: string|null,
     *         meaning: string,
     *         meanings: array<int, string>,
     *         word_types: array<int, string>,
     *         writing_elements: array<int, string>,
     *         reading_elements: array<int, string>,
     *         word_type: string,
     *         word_k_ele: string,
     *         furigana_r_ele: string,
     *         sense: string|null,
     *         viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null
     *     }>,
     *     pagination: PaginationResource
     * }
     */
    #[Response(type: 'array{items: array<int, array{id: int, uuid: string, word: string, furigana: string, jlpt: string|null, meaning: string, meanings: array<int, string>, word_types: array<int, string>, writing_elements: array<int, string>, reading_elements: array<int, string>, word_type: string, word_k_ele: string, furigana_r_ele: string, sense: string|null, viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null}>, pagination: PaginationResource}')]
    public function index(IndexWordRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();

        $criteria = WordQueryCriteria::forListing(
            page: $validated['page'] ?? Pagination::MIN_PAGE,
            perPage: $validated['per_page'] ?? WordQueryCriteria::DEFAULT_PER_PAGE,
            keyword: $validated['keyword'] ?? null,
            word: $validated['word'] ?? null,
            furigana: $validated['furigana'] ?? null,
            jlpt: $validated['jlpt'] ?? null,
        );

        $result = $this->wordService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $wordListResult = $result->getData();
        $viewerCatalogueStates = [];
        $user = auth('api')->user();

        if ($request->includesViewerCatalogueState() && $user !== null) {
            $viewerCatalogueStates = $this->viewerCatalogueStateService->forItems(
                user: $user,
                itemIds: array_map(
                    static fn ($word): int => $word->getIdValue(),
                    $wordListResult->items,
                ),
                savedType: SavedListType::WORDS,
                knownType: SavedListType::KNOWNWORDS,
            );
        }

        return new WordListResource($wordListResult, $viewerCatalogueStates);
    }

    /**
     * @response array{
     *     id: int,
     *     uuid: string,
     *     word: string,
     *     furigana: string,
     *     jlpt: string|null,
     *     meaning: string,
     *     meanings: array<int, string>,
     *     word_types: array<int, string>,
     *     writing_elements: array<int, string>,
     *     reading_elements: array<int, string>,
     *     word_type: string,
     *     word_k_ele: string,
     *     furigana_r_ele: string,
     *     sense: string|null
     * }
     */
    #[Response(type: 'array{id: int, uuid: string, word: string, furigana: string, jlpt: string|null, meaning: string, meanings: array<int, string>, word_types: array<int, string>, writing_elements: array<int, string>, reading_elements: array<int, string>, word_type: string, word_k_ele: string, furigana_r_ele: string, sense: string|null, viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null, kanjis?: array<int, array{id: int, uuid: string, character: string, onyomi: array<int, string>, kunyomi: array<int, string>, meanings: array<int, string>, nanori: array<int, string>, grade: string|null, stroke_count: int, jlpt: string|null, frequency: int|null, radicals: array<int, string>, radical_parts: array<int, string>, viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null}>, articles?: array<int, array{id: int, uuid: string, title_jp: string, hashtags: array<int, array{id: int, content: string}>, views_total: int, likes_total: int, comments_total: int}>}')]
    public function show(ShowWordRequest $request, string $identifier): JsonResponse|JsonResource
    {
        $result = $this->wordDetailService->findByIdentifier(
            rawurldecode($identifier),
            $request->includes(),
            auth('api')->user(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new WordDetailResource($result->getData());
    }
}

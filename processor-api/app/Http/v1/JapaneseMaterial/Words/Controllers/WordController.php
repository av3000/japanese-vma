<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Words\Services\WordDetailServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Words\Requests\IndexWordRequest;
use App\Http\v1\JapaneseMaterial\Words\Requests\ShowWordRequest;
use App\Http\v1\JapaneseMaterial\Words\Resources\WordDetailResource;
use App\Http\v1\JapaneseMaterial\Words\Resources\WordListResource;
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
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[Response(type: 'WordListResource')]
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
            articleId: isset($validated['article_uuid'])
                ? EntityId::from($validated['article_uuid'])
                : null,
        );

        $result = $this->wordService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $wordListResult = $result->getData();
        $viewerCatalogueStates = [];
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();

        if ($request->includesViewerCatalogueState() && $authenticatedUser !== null) {
            $viewerCatalogueStates = $this->viewerCatalogueStateService->forItems(
                ownerUuid: $authenticatedUser->uuid,
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

    #[Response(type: 'WordDetailResource')]
    public function show(ShowWordRequest $request, string $identifier): JsonResponse|JsonResource
    {
        $result = $this->wordDetailService->findByIdentifier(
            rawurldecode($identifier),
            $request->includes(),
            $this->currentUserProvider->currentAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new WordDetailResource($result->getData());
    }
}

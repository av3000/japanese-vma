<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Words\Services\WordDetailServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Catalogues\Errors\CatalogueErrors;
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
        private readonly CatalogueServiceInterface $catalogueService,
    ) {
    }

    #[Response(type: 'WordListResource')]
    public function index(IndexWordRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();
        $catalogueId = null;

        if (isset($validated['catalogue_uuid'])) {
            $source = $this->catalogueService->resolveItemSource(
                EntityId::from($validated['catalogue_uuid']),
                SavedListType::WORDS,
                $authenticatedUser,
            );

            if ($source->isFailure()) {
                $error = $source->getError();

                // A filter on the wrong kind of catalogue is a rule about one field, so it comes back as a 422 on it.
                if ($error->code === CatalogueErrors::ITEM_FAMILY_MISMATCH) {
                    return TypedResults::validationProblem(['catalogue_uuid' => [(string) $error->errorMessage]]);
                }

                return TypedResults::fromError($error);
            }

            $catalogueId = $source->getData();
        }

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
            catalogueId: $catalogueId,
        );

        $result = $this->wordService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $wordListResult = $result->getData();
        $viewerCatalogueStates = [];

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

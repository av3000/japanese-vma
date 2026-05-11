<?php

namespace App\Http\v1\Articles\Controllers;

use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\v1\Concerns\ResolvesOptionalApiUser;
use App\Http\v1\Articles\Requests\IndexArticleRequest;
use App\Http\v1\Articles\Requests\StoreArticleRequest;
use App\Http\v1\Articles\Requests\UpdateArticleRequest;
use App\Http\v1\Articles\Requests\ArticleDetailRequest;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Engagement\Services\{EngagementServiceInterface, HashtagServiceInterface};
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Http\v1\Articles\Resources\ArticleResource;
use App\Http\v1\Articles\Resources\ArticleDetailResource;
use App\Http\v1\Articles\Resources\ArticleWordCollection;

use App\Domain\Articles\DTOs\{ArticleListDTO, ArticleIncludeOptionsDTO, ArticleCreateDTO, ArticleUpdateDTO};
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\{ObjectTemplateType};
use App\Http\v1\Articles\Resources\ArticleListResource;
use App\Http\v1\Shared\Resources\UuidCreatedResource;
use App\Shared\Http\TypedResults;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleController extends Controller
{
    use ResolvesOptionalApiUser;

    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly LastOperationServiceInterface $lastOperationService,
        private readonly EngagementServiceInterface $engagementService,
        private readonly HashtagServiceInterface $hashtagService,
    ) {}

    /**
     * @response ArticleListResource
     */
    #[Response(type: 'ArticleListResource')]
    public function index(IndexArticleRequest $request): JsonResponse|JsonResource
    {
        // TODO: figure graceful error handling pattern
        $listDTO = ArticleListDTO::fromRequest($request->validated());
        $viewer = $this->resolveOptionalApiUser($request);
        $paginatedArticles = $this->articleService->getArticlesList($listDTO, $viewer);
        $entityIdInts = [];
        $entityUuidStrings = [];

        foreach ($paginatedArticles->getItems() as $article) {
            $entityIdInts[] = $article->getIdValue();
            $entityUuidStrings[] = $article->getUid()->value();
        }

        $statsMap = [];
        $hashtagsMap = [];
        $lastOperationsMap = [];

        if ($listDTO->include_stats_counts) {
            $statsMap = $this->engagementService->enhanceArticlesWithStatsCounts($paginatedArticles);
        }

        if ($listDTO->include_hashtags) {
            $hashtagsMap = $this->hashtagService->getBatchHashtags(
                $entityIdInts,
                ObjectTemplateType::ARTICLE
            );
        }

        $lastOperationsMap = $this->lastOperationService->getBatchLatestStates(
            $entityUuidStrings,
            'kanji_extraction'
        );

        $resources = [];
        // TODO: This supposed to use some Mapper or Builder for mature mapping.
        foreach ($paginatedArticles->getItems() as $article) {
            $stats = $statsMap[$article->getIdValue()] ?? null;
            $hashtags = $hashtagsMap[$article->getIdValue()] ?? [];

            $lastOperation = $lastOperationsMap[$article->getUid()->value()] ?? null;

            // TODO: make options in article resource type agnostic, best accept array and check individual values inside, rather than specifying exact DTO like ArticleListDTO
            $resources[] = new ArticleResource(
                $article,
                [
                    'include_hashtags' => $listDTO->include_hashtags,
                    'include_stats' => $listDTO->include_stats_counts,
                ],
                $stats,
                $hashtags,
                $lastOperation
            );
        }

        return new ArticleListResource([
            'items' => $resources,
            'pagination' => [
                'page' => $paginatedArticles->getPaginator()->currentPage(),
                'per_page' => $paginatedArticles->getPaginator()->perPage(),
                'total' => $paginatedArticles->getPaginator()->total(),
                'last_page' => $paginatedArticles->getPaginator()->lastPage(),
                'has_more' => $paginatedArticles->getPaginator()->hasMorePages(),
            ],
        ]);
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    /**
     * @response UuidCreatedResource
     */
    #[Response(201, type: 'UuidCreatedResource')]
    public function store(StoreArticleRequest $request): JsonResponse|JsonResource
    {
        $createDTO = ArticleCreateDTO::fromRequest($request->validated());

        $result = $this->articleService->createArticle($createDTO, auth('api')->user());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $article = $result->getData();

        return new UuidCreatedResource([
            'uuid' => $article->getUid()->value(),
        ]);
    }

    /**
     * @response ArticleDetailResource
     */
    #[Response(type: 'ArticleDetailResource')]
    public function show(string $uid, ArticleDetailRequest $request): JsonResponse|JsonResource
    {
        $articleUid = EntityId::from($uid);
        $options = ArticleIncludeOptionsDTO::fromRequest($request->validated());
        $viewer = $this->resolveOptionalApiUser($request);
        $result = $this->articleService->getArticle($articleUid, $options, $viewer);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $article = $result->getData();

        $engagementSummary = $this->engagementService->getSingleArticleEngagementSummary(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE,
            $options,
            $viewer !== null
        );

        $hashtags = $this->hashtagService->getHashtags(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE
        );

        $kanjiOperationState = $this->lastOperationService->getLatestState(
            $article->getUid(),
            'kanji_extraction'
        );

        $kanjis = []; // TODO: create service method and use - $japaneseMaterialService->getKanjis($article->getUid());
        $words = []; // TODO: create service method and use $japaneseMaterialService->getWords($article->getUid());

        return new ArticleDetailResource(
            article: $article,
            engagement: $engagementSummary,
            kanjis: $article->getKanjis(),
            words: $words,
            hashtags: $hashtags,
            lastOperation: $kanjiOperationState
        );
    }

    /**
     * @response ArticleResource
     */
    #[Response(type: 'ArticleResource')]
    public function update(string $uid, UpdateArticleRequest $request): JsonResponse|JsonResource
    {
        if (!$request->hasAnyUpdateableFields()) {
            return TypedResults::validationProblem(
                ['fields' => ['At least one field must be provided for update operation']],
                'No fields to update'
            );
        }

        $updateDTO = ArticleUpdateDTO::fromRequest($request->validated());

        // TODO: dispatch update kanjis list job
        $result = $this->articleService->updateArticle(
            $uid,
            $updateDTO,
            auth('api')->user()
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $article = $result->getData();

        $hashtags = $this->hashtagService->getHashtags(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE
        );

        // TODO: returning only Id might be enough for frontend.
        return new ArticleResource(article: $article, hashtags: $hashtags);
    }

    // TODO: refactor to clean architecture
    /**
     * @response array{success: true, message: string}
     */
    #[Response(type: 'array{success: true, message: string}')]
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $articleUuid = EntityId::from($uuid);

            $deleted = $this->articleService->deleteArticle(
                $articleUuid,
                auth('api')->user()
            );

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Article deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // TODO: refactor to clean architecture
    /**
     * @response ArticleWordCollection
     */
    #[Response(type: 'ArticleWordCollection')]
    public function words(Request $request, int $id): JsonResponse
    {
        try {
            $words = $this->articleService->getArticleWords(
                $id,
                $request->get('page'),
                $request->get('per_page')
            );

            return response()->json(new ArticleWordCollection($words));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}

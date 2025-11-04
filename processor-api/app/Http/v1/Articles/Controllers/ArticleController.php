<?php

namespace App\Http\v1\Articles\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\v1\Articles\Requests\IndexArticleRequest;
use App\Http\v1\Articles\Requests\StoreArticleRequest;
use App\Http\v1\Articles\Requests\UpdateArticleRequest;
use App\Http\v1\Articles\Requests\ArticleDetailRequest;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Articles\Services\ArticleKanjiProcessingServiceInterface;
use App\Application\Engagement\Services\{EngagementServiceInterface, HashtagServiceInterface};

use App\Http\v1\Articles\Resources\ArticleResource;
use App\Http\v1\Articles\Resources\ArticleListResource;
use App\Http\v1\Articles\Resources\ArticleDetailResource;
use App\Http\v1\Articles\Resources\ArticleKanjiCollection;
use App\Http\v1\Articles\Resources\ArticleWordCollection;

use App\Domain\Articles\DTOs\{ArticleListDTO, ArticleIncludeOptionsDTO, ArticleCreateDTO};
use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Engagement\Models\EngagementData;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\ObjectTemplateType;

use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleServiceInterface $articleService,
        private EngagementServiceInterface $engagementService,
        private HashtagServiceInterface $hashtagService,
        private ArticleKanjiProcessingServiceInterface $articleKanjiProcessingService
    ) {}

    public function index(IndexArticleRequest $request): JsonResponse {
        // TODO: figure graceful error handling pattern
        $listDTO = ArticleListDTO::fromRequest($request->validated());
        $paginatedArticles = $this->articleService->getArticlesList($listDTO, $request->user());
        $entityIds = array_map(fn($article) => $article->getIdValue(), $paginatedArticles->getItems());

        $statsMap = [];
        $hashtagsMap = [];

        if ($listDTO->include_stats_counts) {
            $statsMap = $this->engagementService->enhanceArticlesWithStatsCounts($paginatedArticles);
        }

        if($listDTO->include_hashtags) {
            $hashtagsMap = $this->hashtagService->getBatchHashtags(
                $entityIds,
                ObjectTemplateType::ARTICLE
            );
        }

        $resources = [];
        // TODO: This supposed to be hidden somehow and not handled in controller, especially engagement data...
        foreach ($paginatedArticles->getItems() as $article) {
            $stats = $statsMap[$article->getIdValue()] ?? null;
            $hashtags = $hashtagsMap[$article->getIdValue()] ?? [];

            $resources[] = new ArticleResource($article, $listDTO, $stats, $hashtags);
        }

        $data = [
            'items' => $resources,
            'pagination' => [
                'page' => $paginatedArticles->getPaginator()->currentPage(),
                'per_page' => $paginatedArticles->getPaginator()->perPage(),
                'total' => $paginatedArticles->getPaginator()->total(),
                'last_page' => $paginatedArticles->getPaginator()->lastPage(),
                'has_more' => $paginatedArticles->getPaginator()->hasMorePages(),
            ],
        ];

        return new JsonResponse($data, 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        try {
            $createDTO = ArticleCreateDTO::fromRequest($request->validated());
            $article = $this->articleService->createArticle($createDTO, auth()->id());

            $hashtags = [];

            // TODO: run the job to create 'tag' column for 'hashtags' from 'uniquehashtags' table first
            if($createDTO->tags && !empty($createDTO->tags)) {
                $this->hashtagService->createTagsForEntity(
                    $article->getIdValue(),
                    ObjectTemplateType::ARTICLE,
                    $createDTO->tags,
                    auth()->id()
                );
            }

            $hashtags = $this->hashtagService->getHashtags(
                $article->getIdValue(),
                ObjectTemplateType::ARTICLE
            );
            // TODO: implement kanji processing queueing.
            // $this->articleKanjiProcessingService->queueKanjiProcessing($article->getUid());

            return response()->json(new ArticleResource(article: $article, hashtags: $hashtags), 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(string $uid, ArticleDetailRequest $request): JsonResponse
    {
        $articleUid = EntityId::from($uid);
        $includeFilterOptionsDTO = ArticleIncludeOptionsDTO::fromRequest($request->validated());
        $article = $this->articleService->getArticle($articleUid, $includeFilterOptionsDTO, auth()->id());
        // TODO: Have 4 separate calls rather than single multi-responsible service.
        // Create findCountByFilter method for each repository
        // return counts here. For richer data, separate filters should be used.
        $engagementData = $this->engagementService->getSingleArticleEngagementData($article->getIdValue(), ObjectTemplateType::ARTICLE, $includeFilterOptionsDTO);
        $hashtags = $this->hashtagService->getHashtags(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE
        );

        $kanjis = []; // TODO: create service method and use - $japaneseMaterialService->getKanjis($article->getUid());
        $words = []; // TODO: create service method and use $japaneseMaterialService->getWords($article->getUid());

        return response()->json(
            new ArticleDetailResource(
                article: $article,
                engagementData: $engagementData,
                kanjis: $kanjis,
                words: $words,
                hashtags: $hashtags
            )
        );
    }

    public function update(UpdateArticleRequest $request, int $id): JsonResponse|ArticleResource {
        // For scalability, this can be moved to background job, meaning, we dispatch a job to update article
        // and return a response that the update request was accepted.
        // Then the client can poll for status.
        try {
            $updateDTO = ArticleUpdateDTO::fromRequest($request->validated());
            $article = $this->articleService->updateArticle(
                $id,
                $updateDTO,
                auth()->id()
            );

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found or unauthorized'
                ], 404);
            }

            return response()->json(new ArticleResource($article));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse {
        try {
            $deleted = $this->articleService->deleteArticle(
                $id,
                auth()->id(),
                auth()->user()->hasRole('admin')
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

    public function kanjis(Request $request, int $id): JsonResponse
    {
        try {
            $kanjis = $this->articleService->getArticleKanjis(
                $id,
                $request->get('page'),
                $request->get('per_page')
            );

            return response()->json(new ArticleKanjiCollection($kanjis));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

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

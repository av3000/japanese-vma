<?php

namespace App\Http\v1\Articles\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\v1\Articles\Requests\IndexArticleRequest;
use App\Http\v1\Articles\Requests\StoreArticleRequest;
use App\Http\v1\Articles\Requests\UpdateArticleRequest;

use App\Application\Articles\Services\ArticleServiceInterface;

use App\Http\v1\Articles\Resources\ArticleResource;
use App\Http\v1\Articles\Resources\ArticleDetailResource;
use App\Http\v1\Articles\Resources\ArticleKanjiCollection;
use App\Http\v1\Articles\Resources\ArticleWordCollection;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Http\v1\Articles\Resources\ArticleListResource;
use Illuminate\Http\JsonResponse;


class ArticleController extends Controller
{
    public function __construct(
        private ArticleServiceInterface $articleService,
        private ArticleKanjiProcessingService $articleKanjiProcessingService
    ) {}

    public function index(IndexArticleRequest $request): JsonResponse {
        // No try-catch needed since DTO is now simple HTTP mapping
        // TODO: figure gracefull error handling pattern
       try {
            $listDTO = ArticleListDTO::fromRequest($request->validated());
            $articles = $this->articleService->getArticles($listDTO, $request->user());

            if ($articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No articles found matching your criteria',
                    'articles' => []
                ], 404);
            }

            return response()->json(new ArticleListResource($articles, $listDTO->includeStats));
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'articles' => []
            ], 422);
        }
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $createDTO = ArticleCreateDTO::fromRequest($request->validated());
        // TODO: shoould we access auth() object here directly?
        $article = $this->articleService->createArticle($createDTO, auth()->id());
        dd($article);
        try {
            $processedArticle = $this->articleKanjiProcessingService->processArticleKanjis($article->getUid());

            return response()->json(new ArticleResource($processedArticle), 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(string $uid): JsonResponse
    {
        $articleUid = EntityId::from($uid);
        $article = $this->articleService->getArticle(
            $articleUid,
            auth()->id()
        );

        return response()->json(new ArticleDetailResource($article));
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

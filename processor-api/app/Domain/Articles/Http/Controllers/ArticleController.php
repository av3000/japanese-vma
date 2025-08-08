<?php

namespace App\Domain\Articles\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Domain\Articles\Http\Requests\IndexArticleRequest;
use App\Domain\Articles\Http\Requests\StoreArticleRequest;
use App\Domain\Articles\Http\Requests\UpdateArticleRequest;

use App\Domain\Articles\Http\Resources\ArticleResource;
use App\Domain\Articles\Http\Resources\ArticleDetailResource;
use App\Domain\Articles\Http\Resources\ArticleKanjiCollection;
use App\Domain\Articles\Http\Resources\ArticleWordCollection;

use App\Domain\Articles\Actions\Retrieval\GetArticlesAction;
use App\Domain\Articles\Actions\Retrieval\GetArticleDetailAction;
use App\Domain\Articles\Actions\Creation\CreateArticleAction;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Http\Resources\ArticleListResource;
use Illuminate\Http\JsonResponse;
use App\Shared\DTOs\PaginationData;

class ArticleController extends Controller
{
     public function index(
        IndexArticleRequest $request,
        GetArticlesAction $getArticlesAction
    ): JsonResponse|ArticleListResource {
        try {
            $indexDTO = ArticleListDTO::fromRequest($request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'articles' => []
            ], 422);
        }

        $includeStats = $request->boolean('include_stats', false);

        $articles = $getArticlesAction->execute($indexDTO, $includeStats);

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No articles found matching your criteria',
                'articles' => []
            ], 404);
        }

        return new ArticleListResource($articles, $includeStats);
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    public function store(
        StoreArticleRequest $request,
        CreateArticleAction $createArticleAction
    ): ArticleResource {
        $createDTO = ArticleCreateDTO::fromRequest($request->validated());
        $article = $createArticleAction->execute($createDTO, auth()->id());
        return new ArticleResource($article);
    }

    public function show(
    int $id,
        GetArticleDetailAction $getArticleDetailAction
    ): JsonResponse|ArticleDetailResource {
        $article = $getArticleDetailAction->execute($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found'
            ], 404);
        }

        return new ArticleDetailResource($article);
    }

    public function update(
        UpdateArticleRequest $request,
        int $id,
        UpdateArticleAction $updateArticleAction
    ): JsonResponse|ArticleResource {
        $updateDTO = ArticleUpdateDTO::fromRequest($request->validated());
        $article = $updateArticleAction->execute($id, $updateDTO, auth()->id());

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found or unauthorized'
            ], 404);
        }

        return new ArticleResource($article);
    }

    public function destroy(
        int $id,
        DeleteArticleAction $deleteArticleAction
    ): JsonResponse {
        $result = $deleteArticleAction->execute($id, auth()->id(), auth()->user()->hasRole('admin'));

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found or unauthorized'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully'
        ]);
    }

    public function kanjis(
        Request $request,
        int $id,
        GetArticleKanjis $getArticleKanjis
    ): ArticleKanjiCollection {
        $pagination = PaginationData::fromRequest($request->all());
        $kanjis = $getArticleKanjis->execute($id, $pagination);
        // TODO: figure if shouldnt JSON be returned here instead of ResourceCollection
        return new ArticleKanjiCollection($kanjis);
    }

    public function words(
        Request $request,
        int $id,
        GetArticleWords $getArticleWords
    ): ArticleWordCollection {
        $pagination = PaginationData::fromRequest($request->all());
        $words = $getArticleWords->execute($id, $pagination);
        // TODO: figure if shouldnt JSON be returned here instead of ResourceCollection
        return new ArticleWordCollection($words);
    }

}

<?php

namespace App\Http\v1\Article\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\v1\Article\Requests\IndexArticleRequest;
use App\Http\v1\Article\Requests\StoreArticleRequest;
use App\Http\v1\Article\Requests\UpdateArticleRequest;

use App\Domain\Articles\Interfaces\Actions\ArticleListActionInterface;
use App\Domain\Articles\Interfaces\Actions\GetArticleDetailActionInterface;
use App\Domain\Articles\Interfaces\Actions\CreateArticleActionInterface;
use App\Domain\Articles\Interfaces\Actions\UpdateArticleActionInterface;
use App\Domain\Articles\Interfaces\Actions\DeleteArticleActionInterface;

use App\Http\v1\Article\Resources\ArticleResource;
use App\Http\v1\Article\Resources\ArticleDetailResource;
use App\Http\v1\Article\Resources\ArticleKanjiCollection;
use App\Http\v1\Article\Resources\ArticleWordCollection;

use App\Domain\Articles\Actions\Retrieval\GetArticlesAction;
use App\Domain\Articles\Actions\Retrieval\GetArticleDetailAction;
use App\Domain\Articles\Actions\Creation\CreateArticleAction;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Http\v1\Article\Resources\ArticleListResource;
use Illuminate\Http\JsonResponse;
use App\Shared\DTOs\PaginationData;


class ArticleController extends Controller
{
    public function index(
        IndexArticleRequest $request,
        ArticleListActionInterface $articleListAction
    ): JsonResponse|ArticleListResource {
        // No try-catch needed since DTO is now simple HTTP mapping
        // TODO: figure gracefull error handling pattern
        $indexDTO = ArticleListDTO::fromRequest($request->validated());

        $articles = $articleListAction->execute($indexDTO, $request->user());

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No articles found matching your criteria',
                'articles' => []
            ], 404);
        }

        return new ArticleListResource($articles, $indexDTO->includeStats);
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    public function store(
        StoreArticleRequest $request,
        CreateArticleActionInterface $createArticleAction
    ): ArticleResource {
        try {
            $createDTO = ArticleCreateDTO::fromRequest($request->validated());
            $article = $createArticleAction->execute($createDTO, auth()->id());
            return response()->json(new ArticleResource($article), Http::HTTP_CREATED);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(
        int $id,
        GetArticleDetailActionInterface $getArticleDetailAction
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
        UpdateArticleActionInterface $updateArticleAction
    ): JsonResponse|ArticleResource {
        // For scalability, this can be moved to background job, meaning, we dispatch a job to update article
        // and return a response that the update request was accepted.
        // Then the client can poll for status.
        $updateDTO = ArticleUpdateDTO::fromRequest($request->validated());
        $article = $updateArticleAction->execute($id, $updateDTO, auth()->id());

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found or unauthorized'
            ], 404);
        }

        return response()->json(new ArticleResource($article), Http::HTTP_ACCEPTED);
    }

    public function destroy(
        int $id,
        DeleteArticleActionInterface $deleteArticleAction
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

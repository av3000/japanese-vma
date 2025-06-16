<?php

// app/Domain/Articles/Http/Controllers/ArticleController.php
namespace App\Domain\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Articles\Http\Requests\IndexArticleRequest;
use App\Domain\Articles\Http\Requests\StoreArticleRequest;
use App\Domain\Articles\Http\Requests\UpdateArticleRequest;

use App\Domain\Articles\Http\Resources\ArticleResource;
use App\Domain\Articles\Http\Resources\ArticleDetailResource;
use App\Domain\Articles\Services\ArticleService;
use App\Domain\Articles\DTOs\ArticleData;
use App\Domain\Articles\DTOs\ArticleUpdateData;
use App\Domain\Articles\DTOs\ArticleIndexData;

class ArticleController extends Controller
{
    public function __construct(private ArticleService $service) {}

    public function index(IndexArticleRequest $request)
    {
        $indexData = ArticleIndexData::fromRequest($request->validated());
        $articles = $this->service->getArticles($indexData);

        return response()->json([
            'success' => true,
            'articles' => $articles,
            'message' => 'articles fetched',
            'imagePath' => $this->getImagePath()
        ]);
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    public function store(StoreArticleRequest $request)
    {
        $article = $this->service->createArticle(
            ArticleData::fromRequest($request->validated()),
            auth()->id()
        );

        return new ArticleResource($article);
    }

    public function show(int $id)
    {

        $wordCount = \DB::table('article_word')->where('article_id', $id)->count();

        $article = $this->service->getArticleWithDetails($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found'
            ], 404);
        }

        return new ArticleDetailResource($article);
    }

    public function update(UpdateArticleRequest $request, int $id)
    {
        $updateData = ArticleUpdateData::fromRequest($request->validated());

        $article = $this->service->updateArticle($id, $updateData, auth()->id());

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found or unauthorized'
            ], 404);
        }

        return new ArticleResource($article);
    }

    public function destroy(int $id)
    {
        $result = $this->service->deleteArticle($id, auth()->id(), auth()->user()->hasRole('admin'));

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
}

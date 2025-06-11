<?php

// app/Domain/Articles/Http/Controllers/ArticleController.php
namespace App\Domain\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Articles\Http\Requests\StoreArticleRequest;
use App\Domain\Articles\Http\Resources\ArticleResource;
use App\Domain\Articles\Http\Resources\ArticleDetailResource;
use App\Domain\Articles\Services\ArticleService;
use App\Domain\Articles\DTOs\ArticleData;

class ArticleController extends Controller
{
    public function __construct(private ArticleService $service) {}

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
        // In controller before calling service
        \Log::info('Article ID: ' . $id);
        $wordCount = \DB::table('article_word')->where('article_id', $id)->count();
        \Log::info('Word count in pivot: ' . $wordCount);
        $article = $this->service->getArticleWithDetails($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found'
            ], 404);
        }

        return new ArticleDetailResource($article);
    }
}


<?php

namespace App\Domains\Articles\ManageArticles\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Articles\StoreArticleRequest;
use App\Domains\Articles\ManageArticles\Services\CreateArticle;
use App\Domains\Articles\ManageArticles\Web\ViewHelpers\ArticleViewHelper;
use App\Traits\JsonRespondController;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    use JsonRespondController;

    /**
     * Create a new article following Monica's pattern
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        try {
            // Prepare data for service (minimal controller logic)
            $data = array_merge(
                $request->validated(),
                ['author_id' => auth()->id()]
            );

            // Execute service - this is where all business logic happens
            $article = (new CreateArticle)->execute($data);

            // Format response using ViewHelper
            return $this->respondCreated([
                'article' => ArticleViewHelper::dto($article, auth()->user()),
                'message' => 'Article created successfully'
            ]);

        } catch (\Exception $e) {
            // Standardized error handling
            return $this->respondServiceError(
                $e->getMessage(),
                $e instanceof \Illuminate\Validation\ValidationException ? 422 : 500
            );
        }
    }
}

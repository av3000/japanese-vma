<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Articles\Http\Models\Article;
use App\Shared\DTOs\PaginationData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetArticleWords
{
    public function execute(int $articleId, PaginationData $pagination): LengthAwarePaginator
    {
        $article = Article::findOrFail($articleId);

        return $article->words()
            ->paginate(
                perPage: $pagination->perPage,
                page: $pagination->page
            );
    }
}

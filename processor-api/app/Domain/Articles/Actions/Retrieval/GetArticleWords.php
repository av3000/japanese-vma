<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;
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

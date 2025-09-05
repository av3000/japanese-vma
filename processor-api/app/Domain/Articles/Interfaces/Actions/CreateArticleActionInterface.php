<?php
namespace App\Domain\Articles\Interfaces\Actions;

use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\Http\Models\Article;

interface CreateArticleActionInterface
{
    /**
     * Create a complete article with all associated data
     *
     * @param ArticleCreateDTO $data The article data transfer object
     * @param int $userId The ID of the user creating the article
     * @return Article The newly created article with relationships loaded
     */
    public function execute(ArticleCreateDTO $data, int $userId): Article;
}

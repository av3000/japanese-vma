<?php
namespace App\Domain\Articles\Interfaces\Actions;

use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\Models\Article;

interface UpdateArticleActionInterface
{
    /**
     * Update an existing article with the provided data
     *
     * @param int $id The ID of the article to update
     * @param ArticleUpdateDTO $data The data to update the article with
     * @param int $userId The ID of the user performing the update (for authorization)
     * @return Article|null The updated article or null if not found/authorized
     */
    public function execute(int $id, ArticleUpdateDTO $data, int $userId): ?Article;
}

<?php
namespace App\Domain\Articles\Interfaces\Actions;

interface DeleteArticleActionInterface
{
    /**
     * Delete an article if the user has permission
     *
     * @param int $id The ID of the article to delete
     * @param int $userId The ID of the user attempting the deletion
     * @param bool $isAdmin Whether the user has admin privileges
     * @return bool True if deletion was successful, false otherwise
     */
    public function execute(int $id, int $userId, bool $isAdmin = false): bool;
}

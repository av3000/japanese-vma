<?php

namespace App\Domain\Articles\Interfaces;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    /**
     * Get paginated articles with filters
     *
     * @param ArticleListDTO $filters
     * @param User|null $user
     * @return LengthAwarePaginator
     */
    public function getPaginated(ArticleListDTO $filters, ?User $user = null): LengthAwarePaginator;

    /**
     * Find article by ID
     *
     * @param int $id
     * @return Article|null
     */
    public function findById(int $id): ?Article;
}

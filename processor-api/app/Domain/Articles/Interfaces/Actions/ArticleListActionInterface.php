<?php

namespace App\Domain\Articles\Interfaces;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleListActionInterface
{
    /**
     * Get articles list with optional enhancements
     *
     * @param ArticleListDTO $dto
     * @param User|null $user
     * @return LengthAwarePaginator
     */
    public function execute(ArticleListDTO $dto, ?User $user = null): LengthAwarePaginator;
}

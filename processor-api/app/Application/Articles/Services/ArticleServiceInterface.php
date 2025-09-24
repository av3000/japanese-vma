<?php
namespace App\Application\Articles\Services;

use App\Domain\Articles\DTOs\{ArticleCreateDTO, ArticleUpdateDTO, ArticleListDTO};
use App\Domain\Articles\Models\Articles;

use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;

use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleServiceInterface
{
    public function createArticle(ArticleCreateDTO $dto, int $userId);
    public function getArticle(int $id, ?int $userId = null): ?PersistenceArticle;
    public function getArticlesList(ArticleListDTO $dto, ?User $user = null): Articles;
    public function updateArticle(int $id, ArticleUpdateDTO $dto, int $userId): ?PersistenceArticle;
    public function deleteArticle(int $id, int $userId, bool $isAdmin = false): bool;
    public function getArticleKanjis(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;
    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;
}

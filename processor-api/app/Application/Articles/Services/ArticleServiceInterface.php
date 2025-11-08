<?php
namespace App\Application\Articles\Services;

use App\Domain\Articles\DTOs\{ArticleCreateDTO, ArticleIncludeOptionsDTO, ArticleUpdateDTO, ArticleListDTO};
use App\Domain\Articles\Models\{Articles, Article};
use App\Domain\Shared\ValueObjects\{EntityId};

use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;

use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleServiceInterface
{
    public function createArticle(ArticleCreateDTO $dto, int $userId);
    public function getArticle(EntityId $articleUid, ArticleIncludeOptionsDTO $dto, ?User $user = null): ?Article;
    public function getArticlesList(ArticleListDTO $dto, ?User $user = null): Articles;
    public function updateArticle(int $id, ArticleUpdateDTO $dto, int $userId): ?PersistenceArticle;
    public function deleteArticle(EntityId $articleUuid, User $user): bool;
    public function getArticleKanjis(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;
    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;
}

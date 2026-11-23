<?php

declare(strict_types=1);

namespace App\Domain\AdminDashboard\DTOs;

use App\Domain\Shared\Enums\ArticleStatus;

/**
 * Site-wide content totals for the admin dashboard.
 */
final readonly class ContentStatsDTO
{
    /**
     * @param array<int, int> $articlesByStatus count per ArticleStatus value; every status present
     */
    public function __construct(
        public int $users,
        public int $newUsersLastWeek,
        public array $articlesByStatus,
        public int $publicCatalogues,
        public int $posts,
        public int $newCommentsLastWeek,
    ) {
    }

    public function articles(): int
    {
        return array_sum($this->articlesByStatus);
    }

    public function articlesWithStatus(ArticleStatus $status): int
    {
        return $this->articlesByStatus[$status->value] ?? 0;
    }

    /**
     * @return array{users: int, new_users_last_week: int, articles_by_status: array<int, int>, public_catalogues: int, posts: int, new_comments_last_week: int}
     */
    public function toArray(): array
    {
        return [
            'users' => $this->users,
            'new_users_last_week' => $this->newUsersLastWeek,
            'articles_by_status' => $this->articlesByStatus,
            'public_catalogues' => $this->publicCatalogues,
            'posts' => $this->posts,
            'new_comments_last_week' => $this->newCommentsLastWeek,
        ];
    }

    /**
     * @param array{users: int, new_users_last_week: int, articles_by_status: array<int, int>, public_catalogues: int, posts: int, new_comments_last_week: int} $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            users: $values['users'],
            newUsersLastWeek: $values['new_users_last_week'],
            articlesByStatus: $values['articles_by_status'],
            publicCatalogues: $values['public_catalogues'],
            posts: $values['posts'],
            newCommentsLastWeek: $values['new_comments_last_week'],
        );
    }
}

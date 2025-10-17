<?php
namespace App\Domain\Engagement\Models;

use Illuminate\Support\Collection;

readonly class EngagementData
{
    public function __construct(
        private ?Collection $views = null,
        private ?Collection $likes = null,
        private ?Collection $downloads = null,
        private ?Collection $comments = null
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    public function getViews(): ?Collection
    {
        return $this->views;
    }

    public function getLikes(): ?Collection
    {
        return $this->likes;
    }

    public function getDownloads(): ?Collection
    {
        return $this->downloads;
    }

    public function getComments(): ?Collection
    {
        return $this->comments;
    }

    public function getViewsCount(): int
    {
        return $this->views?->count() ?? 0;
    }

    public function getLikesCount(): int
    {
        return $this->likes?->count() ?? 0;
    }

    public function getDownloadsCount(): int
    {
        return $this->downloads?->count() ?? 0;
    }

    public function getCommentsCount(): int
    {
        return $this->comments?->count() ?? 0;
    }

    public function hasAnyData(): bool
    {
        return $this->views !== null ||
               $this->likes !== null ||
               $this->downloads !== null ||
               $this->comments !== null;
    }
}

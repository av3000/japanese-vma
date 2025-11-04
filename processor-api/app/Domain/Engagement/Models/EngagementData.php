<?php
namespace App\Domain\Engagement\Models;

readonly class EngagementData
{
    public function __construct(
        private ?array $views = [],
        private ?array $likes = [],
        private ?array $downloads = [],
        private ?array $comments = []
    ) {}

    public function getViews(): ?array
    {
        return $this->views;
    }

    public function getLikes(): ?array
    {
        return $this->likes;
    }
    public function getDownloads(): ?array
    {
        return $this->downloads;
    }
    public function getComments(): ?array
    {
        return $this->comments;
    }

    public function hasAnyData(): bool
    {
        return $this->views ||
               $this->likes ||
               $this->downloads ||
               $this->comments;
    }
}

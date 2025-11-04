<?php
namespace App\Domain\Shared\DTOs;

readonly class IncludeEngagementOptionsDTO
{
    public function __construct(
        public bool $include_likes = false,
        public bool $include_views = false,
        public bool $include_downloads = false,
        public bool $include_comments = false,
    ) {}

    public function hasAnyEngagement(): bool
    {
        return $this->include_likes ||
               $this->include_views ||
               $this->include_downloads ||
               $this->include_comments;
    }
}

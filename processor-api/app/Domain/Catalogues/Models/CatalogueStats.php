<?php

namespace App\Domain\Catalogues\Models;

readonly class CatalogueStats
{
    public function __construct(
        private int $likesCount = 0,
        private int $downloadsCount = 0,
        private int $viewsCount = 0,
        private int $commentsCount = 0,
    ) {}

    public function getLikesCount(): int { return $this->likesCount; }
    public function getDownloadsCount(): int { return $this->downloadsCount; }
    public function getViewsCount(): int { return $this->viewsCount; }
    public function getCommentsCount(): int { return $this->commentsCount; }
}

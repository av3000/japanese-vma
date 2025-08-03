<?php
namespace App\Domain\Articles\DTOs;

class StatsDTO
{
    public function __construct(
        public readonly int $likesTotal = 0,
        public readonly int $downloadsTotal = 0,
        public readonly int $viewsTotal = 0,
        public readonly int $commentsTotal = 0
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            likesTotal: $model->likesTotal ?? 0,
            downloadsTotal: $model->downloadsTotal ?? 0,
            viewsTotal: $model->viewsTotal ?? 0,
            commentsTotal: $model->commentsTotal ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'likesTotal' => $this->likesTotal,
            'downloadsTotal' => $this->downloadsTotal,
            'viewsTotal' => $this->viewsTotal,
            'commentsTotal' => $this->commentsTotal,
        ];
    }
}

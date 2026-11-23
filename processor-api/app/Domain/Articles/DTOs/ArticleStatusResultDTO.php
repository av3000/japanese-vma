<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class ArticleStatusResultDTO
{
    public function __construct(
        public EntityId $uuid,
        public ArticleStatus $status,
    ) {
    }
}

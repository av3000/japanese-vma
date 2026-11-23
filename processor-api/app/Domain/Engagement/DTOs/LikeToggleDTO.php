<?php

declare(strict_types=1);

namespace App\Domain\Engagement\DTOs;

use App\Domain\Engagement\Enums\LikeTargetType;
use App\Domain\Shared\ValueObjects\UserId;

readonly class LikeToggleDTO
{
    public function __construct(
        public UserId $userId,
        public LikeTargetType $target,
        public int $entityId,
    ) {
    }
}

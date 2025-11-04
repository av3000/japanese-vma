<?php
namespace App\Domain\Engagement\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

readonly class LikeFilterDTO
{
    public function __construct(
        public int $entityId,
        public ObjectTemplateType $objectType,
        public ?string $likeValue = null, // 1 or -1 | upvote or downvote. So far only 1 is used as there is not dislike functionality
    ) {}
}

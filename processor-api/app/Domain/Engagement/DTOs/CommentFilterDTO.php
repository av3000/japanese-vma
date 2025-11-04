<?php
namespace App\Domain\Engagement\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;

readonly class CommentFilterDTO
{
    public function __construct(
        public string $entityId,
        public ObjectTemplateType $objectType,
        // public ?int $parentCommentId = null, TODO: see if could be useful later.
    ) {}
}

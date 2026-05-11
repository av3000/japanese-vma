<?php

namespace App\Domain\Comments\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;

class CommentCreateDTO
{
    public function __construct(
        public readonly int $entity_id,
        public readonly ObjectTemplateType $entity_type,
        public readonly EntityId $entity_uuid,
        public readonly string $content,
        public readonly ?int $parent_comment_id = null,
    ) {
    }

    /**
     * @param  array{entity_id: int, entity_type: string, entity_uuid: string, content: string, parent_comment_id?: int|null}  $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            entity_id: $validated['entity_id'],
            entity_type: ObjectTemplateType::from($validated['entity_type']),
            entity_uuid: EntityId::from($validated['entity_uuid']),
            content: $validated['content'],
            parent_comment_id: $validated['parent_comment_id'] ?? null,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * The entity a comment hangs off, as far as a moderator needs it: what it is and what it is
 * called. A parent that no longer exists keeps its type and id but has no uuid or label.
 */
final readonly class CommentParentDTO
{
    public function __construct(
        public ObjectTemplateType $type,
        public int $id,
        public ?string $uuid,
        public ?string $label,
    ) {
    }

    public static function missing(ObjectTemplateType $type, int $id): self
    {
        return new self($type, $id, null, null);
    }

    public static function key(ObjectTemplateType $type, int $id): string
    {
        return $type->value.':'.$id;
    }

    public function exists(): bool
    {
        return $this->uuid !== null;
    }
}

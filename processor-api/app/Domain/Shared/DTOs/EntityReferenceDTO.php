<?php

declare(strict_types=1);

namespace App\Domain\Shared\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Another entity as a pointer needs it - the parent of a comment, an item in a catalogue: what it
 * is and what it is called. An entity that no longer exists keeps its type and id but has no
 * uuid or label.
 */
final readonly class EntityReferenceDTO
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

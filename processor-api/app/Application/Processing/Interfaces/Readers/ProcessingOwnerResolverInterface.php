<?php

declare(strict_types=1);

namespace App\Application\Processing\Interfaces\Readers;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Shared\ValueObjects\EntityId;

/**
 * Who owns a processed entity, so its status can also be pushed on the owner's private
 * channel (ADR 0002 point 5, issue #263). Returns null when the entity or its owner is gone.
 */
interface ProcessingOwnerResolverInterface
{
    public function ownerUuid(ProcessingEntityType $entityType, EntityId $entityId): ?string;
}

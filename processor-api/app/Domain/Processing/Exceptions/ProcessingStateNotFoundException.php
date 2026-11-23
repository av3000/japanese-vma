<?php

declare(strict_types=1);

namespace App\Domain\Processing\Exceptions;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use Exception;

/**
 * A status transition was asked for a row that does not exist (audit F-23, issue #267).
 *
 * Transitions used to return null here, so a wrong or missing id produced no error, no log and
 * no broadcast: the status update was simply lost and the client waited forever. Every
 * transition starts from a row created by `startOrReset`, so a missing row means the caller is
 * wrong about which entity or task it is processing. That is a bug, and it should be loud.
 */
final class ProcessingStateNotFoundException extends Exception
{
    public function __construct(
        public readonly ProcessingEntityType $entityType,
        public readonly string $entityId,
        public readonly ProcessingTaskType $task,
    ) {
        parent::__construct(sprintf(
            'No processing state for %s [%s] task [%s].',
            $entityType->value,
            $entityId,
            $task->value,
        ));
    }

    public static function for(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
    ): self {
        return new self($entityType, $entityId->value(), $task);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum LastOperationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /** The article content changed after this run was queued; its result was never applied. */
    case SUPERSEDED = 'superseded';

    public function isTerminal(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED || $this === self::SUPERSEDED;
    }

    /**
     * @return list<string>
     */
    public static function nonTerminalValues(): array
    {
        return [self::PENDING->value, self::PROCESSING->value];
    }
}

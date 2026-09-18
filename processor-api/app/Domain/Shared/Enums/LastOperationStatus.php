<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum LastOperationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function isTerminal(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED;
    }

    /**
     * @return list<string>
     */
    public static function nonTerminalValues(): array
    {
        return [self::PENDING->value, self::PROCESSING->value];
    }
}

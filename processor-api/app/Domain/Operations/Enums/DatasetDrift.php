<?php

declare(strict_types=1);

namespace App\Domain\Operations\Enums;

/**
 * How a dictionary table compares with what the last import wrote into it.
 */
enum DatasetDrift: string
{
    case InSync = 'in_sync';

    /** Expected for the sentences table, which also holds user-authored rows. */
    case RowsAdded = 'rows_added';

    case RowsMissing = 'rows_missing';
    case NeverImported = 'never_imported';
    case TableMissing = 'table_missing';

    public static function compare(?int $recorded, ?int $live): self
    {
        return match (true) {
            $live === null => self::TableMissing,
            $recorded === null => self::NeverImported,
            $live === $recorded => self::InSync,
            $live > $recorded => self::RowsAdded,
            default => self::RowsMissing,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::InSync => 'In sync',
            self::RowsAdded => 'Rows added since import',
            self::RowsMissing => 'Rows missing',
            self::NeverImported => 'Never imported here',
            self::TableMissing => 'Table missing',
        };
    }

    public function needsAttention(): bool
    {
        return $this === self::RowsMissing || $this === self::TableMissing;
    }
}

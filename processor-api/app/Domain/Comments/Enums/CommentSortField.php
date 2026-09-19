<?php

declare(strict_types=1);

namespace App\Domain\Comments\Enums;

enum CommentSortField: string
{
    case CREATED_AT = 'created_at';
    case UPDATED_AT = 'updated_at';

    public function label(): string
    {
        return match ($this) {
            self::CREATED_AT => 'Creation Date',
            self::UPDATED_AT => 'Last Modified',
        };
    }
}

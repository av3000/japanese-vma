<?php
namespace App\Domain\Shared\Enums;

enum ArticleSortField: string
{
    case CREATED_AT = 'created_at';
    case UPDATED_AT = 'updated_at';
    case TITLE_JP = 'title_jp';
    case TITLE_EN = 'title_en';

    public function label(): string
    {
        return match($this) {
            self::CREATED_AT => 'Creation Date',
            self::UPDATED_AT => 'Last Modified',
            self::TITLE_JP => 'Japanese Title',
            self::TITLE_EN => 'English Title',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Error;

final readonly class KanjiErrors
{
    public static function notFound(string $identifier): Error
    {
        return new Error(
            'KANJI_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Kanji with identifier '{$identifier}' not found."
        );
    }
}

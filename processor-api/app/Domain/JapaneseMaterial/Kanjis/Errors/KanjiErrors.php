<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

final readonly class KanjiErrors
{
    public static function notFound(string $identifier): ResultError
    {
        return new ResultError(
            'KANJI_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Kanji with identifier '{$identifier}' not found."
        );
    }
}

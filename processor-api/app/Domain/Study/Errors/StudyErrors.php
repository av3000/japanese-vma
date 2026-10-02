<?php

declare(strict_types=1);

namespace App\Domain\Study\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class StudyErrors
{
    public static function catalogueTypeNotSupported(string $catalogueUid, string $typeLabel): ResultError
    {
        return new ResultError(
            code: 'Study.CatalogueTypeNotSupported',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Catalogue type not supported for study',
            detail: "Catalogue {$catalogueUid} is a {$typeLabel} catalogue; only kanji, words and radicals catalogues can be studied",
            errorMessage: "Catalogue {$catalogueUid} is a {$typeLabel} catalogue; only kanji, words and radicals catalogues can be studied",
        );
    }

    public static function invalidFieldCombination(string $catalogueUid, string $prompt, string $answer, string $mode): ResultError
    {
        return new ResultError(
            code: 'Study.InvalidFieldCombination',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Invalid flashcard configuration',
            detail: "Catalogue {$catalogueUid} cannot be studied with prompt {$prompt}, answer {$answer} and mode {$mode}",
            errorMessage: "Catalogue {$catalogueUid} cannot be studied with prompt {$prompt}, answer {$answer} and mode {$mode}",
        );
    }

    public static function noEligibleCards(string $catalogueUid, string $answer): ResultError
    {
        return new ResultError(
            code: 'Study.NoEligibleCards',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'No eligible cards',
            detail: "No item in catalogue {$catalogueUid} has a {$answer} to answer with",
            errorMessage: "No item in catalogue {$catalogueUid} has a {$answer} to answer with",
        );
    }
}

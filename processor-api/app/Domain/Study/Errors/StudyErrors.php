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

    /**
     * Returned both when the uuid is unknown and when the session belongs to someone else.
     * The endpoint must not confirm that another learner's session exists, so the two are
     * deliberately indistinguishable (the same shape as CatalogueErrors::legacyIdentityNotFound).
     */
    public static function sessionNotFound(string $sessionUid): ResultError
    {
        return new ResultError(
            code: 'Study.SessionNotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Study session not found',
            detail: "Study session {$sessionUid} does not exist",
            errorMessage: "Study session {$sessionUid} does not exist",
        );
    }

    public static function sessionCompleted(string $sessionUid): ResultError
    {
        return new ResultError(
            code: 'Study.SessionCompleted',
            status: HttpStatus::CONFLICT,
            description: 'Study session already completed',
            detail: "Study session {$sessionUid} is complete and takes no more answers",
            errorMessage: "Study session {$sessionUid} is complete and takes no more answers",
        );
    }

    public static function sessionCreationFailed(): ResultError
    {
        return new ResultError(
            code: 'Study.SessionCreationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Study session creation failed',
            detail: 'An unexpected error occurred while starting the study session',
            errorMessage: 'An unexpected error occurred while starting the study session',
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

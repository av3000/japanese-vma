<?php

declare(strict_types=1);

namespace App\Domain\Translations\Enums;

/**
 * The only two states a key has for a locale. Both are derived, never stored: a key is Translated
 * when a non-empty override exists for it, and Missing otherwise.
 */
enum TranslationState: string
{
    case Translated = 'translated';
    case Missing = 'missing';
}

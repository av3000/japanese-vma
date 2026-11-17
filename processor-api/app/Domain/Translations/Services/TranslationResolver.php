<?php

declare(strict_types=1);

namespace App\Domain\Translations\Services;

use App\Domain\Translations\DTOs\TranslationCompletionDTO;
use App\Domain\Translations\Enums\TranslationState;

/**
 * The override rule, and the only place it lives: a non-empty override for a key that exists in
 * English wins, and anything else is the English text. An override whose key is gone from English
 * (an orphan) is ignored everywhere.
 *
 * Pure, with no IO. The read service (HTTP bundle) and the edit service (Filament) both ask it, so a
 * bundle and a completion count can never disagree about what counts as translated.
 */
final class TranslationResolver
{
    /**
     * The complete bundle: every English key, in English order, translated where an override exists.
     *
     * @param array<string, string> $source The English catalogue.
     * @param array<string, string> $overrides Stored values of one locale, orphans included.
     *
     * @return array<string, string>
     */
    public function merge(array $source, array $overrides): array
    {
        $merged = [];

        foreach ($source as $key => $english) {
            $override = $overrides[$key] ?? null;

            $merged[$key] = $this->state($override) === TranslationState::Translated ? $override : $english;
        }

        return $merged;
    }

    /** Translated for text with something other than whitespace in it; Missing for null, empty or blank. */
    public function state(?string $override): TranslationState
    {
        return $override !== null && trim($override) !== ''
            ? TranslationState::Translated
            : TranslationState::Missing;
    }

    /**
     * Valid translated keys over English keys. Orphans and blank overrides do not count.
     *
     * @param array<string, string> $source
     * @param array<string, string> $overrides
     */
    public function completion(array $source, array $overrides): TranslationCompletionDTO
    {
        $translated = 0;

        foreach ($source as $key => $_english) {
            if ($this->state($overrides[$key] ?? null) === TranslationState::Translated) {
                $translated++;
            }
        }

        $total = count($source);

        return new TranslationCompletionDTO(
            translated: $translated,
            total: $total,
            percent: $total === 0 ? 0 : intdiv($translated * 100, $total),
        );
    }
}

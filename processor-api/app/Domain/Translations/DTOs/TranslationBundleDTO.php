<?php

declare(strict_types=1);

namespace App\Domain\Translations\DTOs;

/**
 * One complete language bundle: every English key, in the language of `locale`.
 */
final readonly class TranslationBundleDTO
{
    /**
     * @param array<string, string> $messages Flat dotted key to text, never empty.
     */
    public function __construct(
        public string $locale,
        public string $fallbackLocale,
        public array $messages,
    ) {
    }
}

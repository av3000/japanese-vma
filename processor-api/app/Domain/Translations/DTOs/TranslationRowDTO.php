<?php

declare(strict_types=1);

namespace App\Domain\Translations\DTOs;

use App\Domain\Translations\Enums\TranslationState;

/**
 * One English key as a translator sees it for one locale.
 */
final readonly class TranslationRowDTO
{
    /**
     * @param string $key Dotted semantic key, for example `auth.login.email`.
     * @param string $source The English text.
     * @param string|null $value The translation, trimmed. Null when the key is Missing, which includes a
     *                           stored value that is blank.
     * @param TranslationState $state Translated when `$value` is text, Missing otherwise.
     */
    public function __construct(
        public string $key,
        public string $source,
        public ?string $value,
        public TranslationState $state,
    ) {
    }
}

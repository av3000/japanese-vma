<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Concerns;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;

/**
 * Replaces the English catalogue with a fixed list, so a translation page test does not depend on
 * what the real `ui.php` happens to hold.
 */
trait UsesTranslationCatalogue
{
    /**
     * @param array<string, string> $messages
     */
    protected function useCatalogue(array $messages): void
    {
        $this->app->instance(EnglishSourceCatalogueInterface::class, new class($messages) implements EnglishSourceCatalogueInterface
        {
            /** @param array<string, string> $messages */
            public function __construct(private readonly array $messages)
            {
            }

            public function messages(): array
            {
                return $this->messages;
            }

            public function find(string $key): ?string
            {
                return $this->messages[$key] ?? null;
            }
        });
    }
}

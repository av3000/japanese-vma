<?php

declare(strict_types=1);

namespace App\Application\Translations\Interfaces\Sources;

/**
 * The code-owned English UI labels (`resources/lang/en/ui.php`), flattened to dotted keys.
 *
 * This is the only way to read them: services, Filament pages and models go through this port,
 * never the language file (guarded by `Tests\Unit\Translations\SourceCatalogueBoundaryTest`).
 */
interface EnglishSourceCatalogueInterface
{
    /**
     * Every English label, keyed by its dotted semantic key (for example `auth.login.email`).
     * Every value is non-empty text.
     *
     * @return array<string, string>
     */
    public function messages(): array;

    /** The English text of one key, or null when the key is not in the catalogue. */
    public function find(string $key): ?string;
}

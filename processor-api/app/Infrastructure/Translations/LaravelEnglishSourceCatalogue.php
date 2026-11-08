<?php

declare(strict_types=1);

namespace App\Infrastructure\Translations;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Domain\Translations\Models\TranslationLocale;
use Illuminate\Contracts\Translation\Loader;
use InvalidArgumentException;

/**
 * Reads the `ui` group of the English language files through Laravel's translation loader and
 * flattens the nested arrays to dotted keys. The only reader of `resources/lang/en/ui.php`.
 *
 * Anything that would make a bundle wrong is a developer error and throws, so the test suite
 * (which loads the real catalogue) fails before it can reach a client:
 *  - an empty catalogue, which is what Laravel's loader returns for a file it cannot find (for
 *    example after `lang:publish` moves the lang path from `resources/lang` to `lang/`);
 *  - an empty, whitespace-only or non-text value, or an empty group;
 *  - a key segment that contains a dot, which could collide with a nested path and could not be
 *    rebuilt into nested objects by a client.
 */
class LaravelEnglishSourceCatalogue implements EnglishSourceCatalogueInterface
{
    private const GROUP = 'ui';

    /** @var array<string, string>|null */
    private ?array $messages = null;

    public function __construct(
        private readonly Loader $loader,
    ) {
    }

    public function messages(): array
    {
        if ($this->messages !== null) {
            return $this->messages;
        }

        $messages = $this->flatten($this->loader->load(TranslationLocale::FALLBACK_CODE, self::GROUP));

        if ($messages === []) {
            throw new InvalidArgumentException(
                'The English UI catalogue is empty: resources/lang/en/ui.php is missing, moved or has no keys.',
            );
        }

        return $this->messages = $messages;
    }

    public function find(string $key): ?string
    {
        return $this->messages()[$key] ?? null;
    }

    /**
     * @param array<array-key, mixed> $nested
     *
     * @return array<string, string>
     */
    private function flatten(array $nested, string $prefix = ''): array
    {
        $flat = [];

        foreach ($nested as $segment => $value) {
            $key = $prefix === '' ? (string) $segment : "{$prefix}.{$segment}";

            if (str_contains((string) $segment, '.')) {
                throw new InvalidArgumentException("English UI key segment '{$segment}' (in '{$key}') must not contain a dot: nest the array instead.");
            }

            if (is_array($value)) {
                if ($value === []) {
                    throw new InvalidArgumentException("English UI group '{$key}' must not be empty.");
                }

                $flat += $this->flatten($value, $key);

                continue;
            }

            if (! is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException("English UI label '{$key}' must be non-empty text.");
            }

            $flat[$key] = $value;
        }

        return $flat;
    }
}

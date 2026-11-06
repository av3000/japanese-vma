<?php

declare(strict_types=1);

namespace App\Infrastructure\Translations;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use Illuminate\Contracts\Translation\Loader;
use InvalidArgumentException;

/**
 * Reads the `ui` group of the English language files through Laravel's translation loader and
 * flattens the nested arrays to dotted keys. The only reader of `resources/lang/en/ui.php`.
 *
 * An empty or non-text value is a developer error: it throws, so the test suite (which loads the
 * real catalogue) fails before an empty label can reach a bundle.
 */
class LaravelEnglishSourceCatalogue implements EnglishSourceCatalogueInterface
{
    private const LOCALE = 'en';

    private const GROUP = 'ui';

    /** @var array<string, string>|null */
    private ?array $messages = null;

    public function __construct(
        private readonly Loader $loader,
    ) {
    }

    public function messages(): array
    {
        return $this->messages ??= $this->flatten($this->loader->load(self::LOCALE, self::GROUP));
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

            if (is_array($value)) {
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

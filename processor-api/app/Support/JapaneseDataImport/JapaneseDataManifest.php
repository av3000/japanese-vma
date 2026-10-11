<?php

declare(strict_types=1);

namespace App\Support\JapaneseDataImport;

use RuntimeException;

/**
 * The list of dictionary datasets: which dump file fills which table, in import order. The
 * importer and the admin panel's import status page read it from here, so they cannot disagree.
 */
class JapaneseDataManifest
{
    public const SENTINEL_TASK = 'japanese-data-import';

    public function __construct(
        private readonly string $path = 'database/japanese-data-pg/manifest.php',
    ) {
    }

    /**
     * @return array<int,array{table:string,source:string}>
     */
    public function datasets(): array
    {
        $manifestPath = base_path($this->path);
        $manifest = is_file($manifestPath) ? require $manifestPath : null;

        if (! is_array($manifest) || $manifest === []) {
            throw new RuntimeException("Import manifest is missing or empty: {$manifestPath}");
        }

        return $manifest;
    }

    /**
     * @param array{table:string,source:string} $dataset
     */
    public function sourcePath(array $dataset): string
    {
        return base_path($dataset['source']);
    }

    /**
     * @param array{table:string,source:string} $dataset
     */
    public function sourceExists(array $dataset): bool
    {
        return is_file($this->sourcePath($dataset));
    }
}

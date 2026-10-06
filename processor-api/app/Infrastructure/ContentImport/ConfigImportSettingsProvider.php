<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport;

use App\Application\ContentImport\Interfaces\Providers\ImportSettingsProviderInterface;
use App\Domain\ContentImport\ValueObjects\ImportRunSettings;
use LogicException;

/**
 * Reads `content_import.defaults`, overridden key by key by `content_import.sources.<key>`.
 * The defaults live in the config file only; a missing key is a configuration error, not a
 * silent zero.
 */
class ConfigImportSettingsProvider implements ImportSettingsProviderInterface
{
    public function for(string $sourceKey): ImportRunSettings
    {
        $settings = array_merge(
            (array) config('content_import.defaults', []),
            (array) config("content_import.sources.{$sourceKey}", []),
        );

        $read = function (string $key) use ($settings, $sourceKey): mixed {
            if (! array_key_exists($key, $settings)) {
                throw new LogicException("content_import has no {$key} setting for {$sourceKey}");
            }

            return $settings[$key];
        };

        return new ImportRunSettings(
            maxCreatedPerRun: (int) $read('max_created_per_run'),
            maxListed: (int) $read('max_listed'),
            minLeadLength: (int) $read('min_lead_length'),
            excludedGenres: array_values(array_map('strval', (array) $read('excluded_genres'))),
            stalledAfterRuns: (int) $read('stalled_after_runs'),
        );
    }
}

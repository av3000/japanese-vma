<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Operations\Interfaces\Readers\ImportStatusReaderInterface;
use App\Domain\Operations\DTOs\DatasetHealthDTO;
use App\Domain\Operations\DTOs\ImportRunDTO;
use App\Domain\Operations\Enums\DatasetDrift;
use App\Support\JapaneseDataImport\JapaneseDataManifest;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DatabaseImportStatusReader implements ImportStatusReaderInterface
{
    public const LIVE_COUNTS_CACHE_KEY = 'operations:import-status:live-counts:v1';

    public function __construct(
        private readonly JapaneseDataManifest $manifest,
        private readonly CacheRepository $cache,
    ) {
    }

    public function runs(): array
    {
        return DB::table('environment_bootstrap_runs')
            ->where('task', JapaneseDataManifest::SENTINEL_TASK)
            ->orderByDesc('completed_at')
            ->get()
            ->map(static function (object $run): ImportRunDTO {
                $metadata = is_string($run->metadata) ? json_decode($run->metadata, true) : null;
                $datasets = is_array($metadata['datasets'] ?? null) ? $metadata['datasets'] : [];

                return new ImportRunDTO(
                    environment: (string) $run->environment,
                    completedAt: $run->completed_at === null ? null : (string) $run->completed_at,
                    datasets: array_map('intval', $datasets),
                );
            })
            ->values()
            ->all();
    }

    public function datasetHealth(?string $environment): array
    {
        $run = null;

        foreach ($this->runs() as $candidate) {
            if ($environment === null || $candidate->environment === $environment) {
                $run = $candidate;
                break;
            }
        }

        $live = $this->liveCounts();
        $health = [];

        foreach ($this->manifest->datasets() as $dataset) {
            $table = $dataset['table'];
            $recorded = $run?->datasets[$table] ?? null;
            $liveRows = $live[$table]['rows'] ?? null;

            $health[] = new DatasetHealthDTO(
                table: $table,
                source: $dataset['source'],
                sourcePresent: $this->manifest->sourceExists($dataset),
                recordedRows: $recorded,
                liveRows: $liveRows,
                drift: DatasetDrift::compare($recorded, $liveRows),
                sequenceBehind: $live[$table]['sequence_behind'] ?? null,
            );
        }

        return $health;
    }

    public function refreshLiveCounts(): void
    {
        try {
            $this->cache->forget(self::LIVE_COUNTS_CACHE_KEY);
        } catch (Throwable $exception) {
            Log::warning('Import status cache forget failed', ['error' => $exception->getMessage()]);
        }
    }

    /**
     * @return array<string, array{rows: int|null, sequence_behind: bool|null}>
     */
    private function liveCounts(): array
    {
        try {
            return $this->cache->remember(
                self::LIVE_COUNTS_CACHE_KEY,
                self::LIVE_COUNT_TTL_SECONDS,
                fn (): array => $this->countTables(),
            );
        } catch (Throwable $exception) {
            Log::warning('Import status cache unavailable, counting live', ['error' => $exception->getMessage()]);

            return $this->countTables();
        }
    }

    /**
     * @return array<string, array{rows: int|null, sequence_behind: bool|null}>
     */
    private function countTables(): array
    {
        $counts = [];

        foreach ($this->manifest->datasets() as $dataset) {
            $table = $dataset['table'];

            if (! Schema::hasTable($table)) {
                $counts[$table] = ['rows' => null, 'sequence_behind' => null];

                continue;
            }

            $counts[$table] = [
                'rows' => DB::table($table)->count(),
                'sequence_behind' => $this->sequenceBehind($table),
            ];
        }

        return $counts;
    }

    /**
     * True when the next id the sequence would hand out is already taken: inserts would then fail
     * with a duplicate key. Null when the table has no serial id.
     */
    private function sequenceBehind(string $table): ?bool
    {
        $sequence = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS name', [$table, 'id'])?->name;

        if ($sequence === null) {
            return null;
        }

        // The sequence name comes from the catalogue, already schema-qualified and quoted.
        $state = DB::selectOne("SELECT last_value, is_called FROM {$sequence}");
        $maxId = DB::table($table)->max('id');

        if ($state === null || $maxId === null) {
            return false;
        }

        $lastIssued = $state->is_called ? (int) $state->last_value : (int) $state->last_value - 1;

        return $lastIssued < (int) $maxId;
    }
}

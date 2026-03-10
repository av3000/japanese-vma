<?php

namespace App\Support\JapaneseDataImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileObject;
use Throwable;

class JapaneseDataImporter
{
    private const SENTINEL_TASK = 'japanese-data-import';

    public function __construct(
        private readonly LegacySqlInsertParser $parser,
    ) {}

    /**
     * @return array{
     *     skipped: bool,
     *     message: string,
     *     datasets: array<string,int>
     * }
     */
    public function import(string $environment, bool $allowRerun = false, ?callable $output = null): array
    {
        $output ??= static function (string $message): void {
        };

        $datasets = $this->loadManifest();
        $sentinel = $this->loadSentinel($environment);

        if ($sentinel !== null && !$allowRerun) {
            return [
                'skipped' => true,
                'message' => "Japanese data import already completed for [{$environment}].",
                'datasets' => [],
            ];
        }

        $this->assertSourcesExist($datasets);
        $this->assertTargetTablesExist($datasets);

        $populatedTables = $this->findPopulatedTables($datasets);

        if ($sentinel === null && !$allowRerun && $populatedTables !== []) {
            $tableList = implode(', ', $populatedTables);

            throw new RuntimeException(
                "Japanese data tables already contain rows without a sentinel record: {$tableList}. ".
                'Verify the environment and rerun with --allow-rerun if you intend to replace the imported data.'
            );
        }

        if ($allowRerun) {
            $output('Rerun requested, clearing target tables first.');
            $this->resetTables($datasets);
        }

        $importedCounts = [];

        foreach ($datasets as $dataset) {
            $table = $dataset['table'];
            $output("Importing {$table} from {$dataset['source']}.");
            $importedCounts[$table] = $this->importDataset($dataset);
        }

        $this->storeSentinel($environment, $importedCounts);

        return [
            'skipped' => false,
            'message' => "Japanese data import completed for [{$environment}].",
            'datasets' => $importedCounts,
        ];
    }

    /**
     * @return array<int,array{table:string,source:string}>
     */
    private function loadManifest(): array
    {
        $manifestPath = base_path('database/japanese-data-pg/manifest.php');
        $manifest = require $manifestPath;

        if (!is_array($manifest) || $manifest === []) {
            throw new RuntimeException("Import manifest is missing or empty: {$manifestPath}");
        }

        return $manifest;
    }

    private function loadSentinel(string $environment): ?object
    {
        return DB::table('environment_bootstrap_runs')
            ->where('environment', $environment)
            ->where('task', self::SENTINEL_TASK)
            ->first();
    }

    /**
     * @param array<int,array{table:string,source:string}> $datasets
     * @return list<string>
     */
    private function findPopulatedTables(array $datasets): array
    {
        $populatedTables = [];

        foreach ($datasets as $dataset) {
            if (DB::table($dataset['table'])->exists()) {
                $populatedTables[] = $dataset['table'];
            }
        }

        return $populatedTables;
    }

    /**
     * @param array<int,array{table:string,source:string}> $datasets
     */
    private function assertSourcesExist(array $datasets): void
    {
        foreach ($datasets as $dataset) {
            $absolutePath = base_path($dataset['source']);

            if (!is_file($absolutePath)) {
                throw new RuntimeException("Missing import source file: {$absolutePath}");
            }
        }
    }

    /**
     * @param array<int,array{table:string,source:string}> $datasets
     */
    private function assertTargetTablesExist(array $datasets): void
    {
        foreach ($datasets as $dataset) {
            if (!Schema::hasTable($dataset['table'])) {
                throw new RuntimeException(
                    "Target table [{$dataset['table']}] does not exist. Run migrations before importing Japanese data."
                );
            }
        }
    }

    /**
     * @param array<int,array{table:string,source:string}> $datasets
     */
    private function resetTables(array $datasets): void
    {
        $tableNames = array_values(array_unique(array_map(
            static fn (array $dataset): string => $dataset['table'],
            $datasets,
        )));

        $reversedTables = array_reverse($tableNames);

        if (DB::connection()->getDriverName() === 'pgsql') {
            $quotedTables = array_map(
                fn (string $table): string => $this->quoteIdentifier($table),
                $reversedTables,
            );

            DB::statement('TRUNCATE TABLE '.implode(', ', $quotedTables).' RESTART IDENTITY CASCADE');

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($reversedTables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @param array{table:string,source:string} $dataset
     */
    private function importDataset(array $dataset): int
    {
        $table = $dataset['table'];
        $sourcePath = base_path($dataset['source']);
        $file = new SplFileObject($sourcePath, 'r');
        $statementBuffer = '';
        $collectingInsert = false;
        $rowCount = 0;

        DB::beginTransaction();

        try {
            while (!$file->eof()) {
                $line = $file->fgets();
                $trimmedLine = ltrim($line);

                if (!$collectingInsert) {
                    if (!str_starts_with($trimmedLine, 'INSERT INTO')) {
                        continue;
                    }

                    $statementBuffer = $line;
                    $collectingInsert = !$this->statementEnded($statementBuffer);

                    if (!$collectingInsert) {
                        $rowCount += $this->processInsertStatement($table, $statementBuffer);
                        $statementBuffer = '';
                    }

                    continue;
                }

                $statementBuffer .= $line;

                if ($this->statementEnded($statementBuffer)) {
                    $rowCount += $this->processInsertStatement($table, $statementBuffer);
                    $statementBuffer = '';
                    $collectingInsert = false;
                }
            }

            if ($collectingInsert) {
                throw new RuntimeException("Incomplete INSERT statement encountered while importing [{$table}].");
            }

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        $this->syncSequence($table);

        return $rowCount;
    }

    private function statementEnded(string $statementBuffer): bool
    {
        return str_ends_with(trim($statementBuffer), ';');
    }

    private function processInsertStatement(string $expectedTable, string $statement): int
    {
        $parsedStatement = $this->parser->parseInsertStatement($statement);

        if ($parsedStatement === null) {
            return 0;
        }

        if ($parsedStatement['table'] !== $expectedTable) {
            throw new RuntimeException(
                "Expected INSERT data for [{$expectedTable}] but found [{$parsedStatement['table']}]."
            );
        }

        $columns = $parsedStatement['columns'];
        $shouldAssignUuid = Schema::hasColumn($expectedTable, 'uuid') && !in_array('uuid', $columns, true);
        $rows = [];

        foreach ($parsedStatement['rows'] as $values) {
            if (count($columns) !== count($values)) {
                throw new RuntimeException("Column/value mismatch while importing [{$expectedTable}].");
            }

            $row = array_combine($columns, $values);

            if ($row === false) {
                throw new RuntimeException("Failed to map values to columns while importing [{$expectedTable}].");
            }

            if ($shouldAssignUuid) {
                $row['uuid'] = (string) Str::uuid();
            }

            $rows[] = $row;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($expectedTable)->insert($chunk);
        }

        return count($rows);
    }

    /**
     * @param array<string,int> $importedCounts
     */
    private function storeSentinel(string $environment, array $importedCounts): void
    {
        $existing = $this->loadSentinel($environment);
        $timestamp = now();
        $payload = [
            'completed_at' => $timestamp,
            'metadata' => json_encode([
                'datasets' => $importedCounts,
            ], JSON_THROW_ON_ERROR),
            'updated_at' => $timestamp,
        ];

        if ($existing !== null) {
            DB::table('environment_bootstrap_runs')
                ->where('environment', $environment)
                ->where('task', self::SENTINEL_TASK)
                ->update($payload);

            return;
        }

        DB::table('environment_bootstrap_runs')->insert($payload + [
            'environment' => $environment,
            'task' => self::SENTINEL_TASK,
            'created_at' => $timestamp,
        ]);
    }

    private function syncSequence(string $table): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $quotedTable = $this->quoteIdentifier($table);

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1), true) FROM {$quotedTable}"
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}

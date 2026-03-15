<?php

namespace App\Console\Commands;

use App\Support\JapaneseDataImport\JapaneseDataImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportJapaneseData extends Command
{
    protected $signature = 'app:import-japanese-data
        {--environment=production : Logical environment name stored in the sentinel table}
        {--allow-rerun : Clear imported tables and run the import again even if it already completed}';

    protected $description = 'Import the Japanese dictionary datasets into the configured database';

    public function __construct(
        private readonly JapaneseDataImporter $importer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $environment = (string) $this->option('environment');
        $allowRerun = (bool) $this->option('allow-rerun');

        $this->info("Preparing Japanese data import for [{$environment}].");

        try {
            $result = $this->importer->import(
                environment: $environment,
                allowRerun: $allowRerun,
                output: function (string $message): void {
                    $this->line($message);
                },
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['skipped'] === true) {
            $this->warn($result['message']);

            return self::SUCCESS;
        }

        foreach ($result['datasets'] as $table => $count) {
            $this->info("Imported {$count} rows into {$table}.");
        }

        $this->info('Japanese data import completed successfully.');

        return self::SUCCESS;
    }
}

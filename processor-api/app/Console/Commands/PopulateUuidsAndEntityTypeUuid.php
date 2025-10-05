<?php
namespace App\Console\Commands;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PopulateUuidsAndEntityTypeUuid extends Command
{
    protected $signature = 'migration:populate-uuids
                           {--batch-size=500 : Number of records per batch}
                           {--delay=100 : Milliseconds to wait between batches}
                           {--max-batches=0 : Maximum batches to process (0 = unlimited)}
                           {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Populate UUIDs for comments incrementally';

    // The entity type UUID is constant for all comments
    private string $entityTypeUuid;

    public function __construct()
    {
        parent::__construct();
        $this->entityTypeUuid = ObjectTemplateType::POST->value;
    }

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $maxBatches = (int) $this->option('max-batches');
        $isDryRun = $this->option('dry-run');

        // Check total records needing UUIDs
        $totalRecords = DB::table('comments')->whereNull('uuid')->count();

        if ($totalRecords === 0) {
            $this->info('✅ All comments already have UUIDs');
            return 0;
        }

        $this->info("📊 Found {$totalRecords} records without UUIDs");

        if ($isDryRun) {
            $this->info("🔍 DRY RUN MODE - No changes will be made");
            $this->info("Would process {$totalRecords} records");
            return 0;
        }

        $this->info("🏷️  Entity type UUID: {$this->entityTypeUuid}");
        $this->info("⚙️  Batch size: {$batchSize}");
        $this->info("⏱️  Delay between batches: {$delay}ms");

        $processed = 0;
        $batchCount = 0;
        $startTime = microtime(true);

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        while (true) {
            if ($maxBatches > 0 && $batchCount >= $maxBatches) {
                $this->newLine(2);
                $this->info("⏸️  Reached max batch limit ({$maxBatches} batches)");
                break;
            }

            $batchProcessed = $this->processBatch($batchSize);

            if ($batchProcessed === 0) {
                break;
            }

            $processed += $batchProcessed;
            $batchCount++;
            $progressBar->advance($batchProcessed);

            usleep($delay * 1000);

            if ($batchCount % 10 === 0) {
                $elapsed = round(microtime(true) - $startTime, 2);
                $rate = round($processed / $elapsed, 2);
                $this->newLine(2);
                $this->info("📈 Progress: {$processed}/{$totalRecords} ({$rate} records/sec)");
                $progressBar->display();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("✅ Processed {$processed} records in {$elapsed} seconds");

        $remaining = DB::table('comments')->whereNull('real_object_uuid')->count();
        if ($remaining > 0) {
            $this->warn("⚠️  {$remaining} records still need real object UUIDs");
            $this->info("💡 Run the command again to continue");
        } else {
            $this->info("🎉 All records now have real object UUIDs!");
        }

        return 0;
    }

    /**
     * Process a single batch, populating both uuid and entity_type_uuid
     */
    private function processBatch(int $batchSize): int
    {
        return DB::transaction(function () use ($batchSize) {
            $records = DB::table('comments')
                ->whereNull('real_object_uuid')
                ->select('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                return 0;
            }

            // Build efficient batch update for both UUID fields
            $caseUuid = [];
            $ids = [];
            $bindings = [];

            foreach ($records as $record) {
                $uuid = Str::uuid()->toString();
                $caseUuid[] = "WHEN id = ? THEN ?";
                $ids[] = $record->id;
                $bindings[] = $record->id;
                $bindings[] = $uuid;
            }

            $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $caseUuidSql = implode(' ', $caseUuid);

            // Update both uuid (unique per record) and entity_type_uuid (same for all)
            $sql = "
                UPDATE comments
                SET
                    real_object_uuid = CASE {$caseUuidSql} END,
                    entity_type_uuid = ?
                WHERE id IN ({$idsPlaceholder})
                AND real_object_uuid IS NULL
            ";

            // Add entity_type_uuid to bindings (once, applies to all records)
            $allBindings = array_merge($bindings, [$this->entityTypeUuid], $ids);

            DB::update($sql, $allBindings);

            return $records->count();
        });
    }
}

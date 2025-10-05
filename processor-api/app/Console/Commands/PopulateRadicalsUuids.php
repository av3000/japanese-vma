<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PopulateRadicalsUuids extends Command
{
    protected $signature = 'radicals:populate-uuids
                           {--batch-size=500 : Number of records per batch}
                           {--delay=100 : Milliseconds to wait between batches}
                           {--max-batches=0 : Maximum batches to process (0 = unlimited)}
                           {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Populate UUID columns in japanese_radicals_bank_long table with batching';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $maxBatches = (int) $this->option('max-batches');

        $this->info('Starting radicals UUID population...');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Show current status before starting
        $this->showCurrentStatus();

        if (!$isDryRun && !$this->confirm('Proceed with updating UUID columns?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Get total records that need UUIDs
        $totalToUpdate = DB::table('japanese_radicals_bank_long')
            ->whereNull('uuid')
            ->count();

        if ($totalToUpdate === 0) {
            $this->info('No radicals need UUID generation. All done!');
            return 0;
        }

        $this->info("Found {$totalToUpdate} radicals needing UUIDs");

        // Process in batches
        $bar = $this->output->createProgressBar($totalToUpdate);
        $bar->start();

        $processed = 0;
        $batchCount = 0;

        do {
            // Get a batch of records needing UUIDs
            $radicalsBatch = DB::table('japanese_radicals_bank_long')
                ->whereNull('uuid')
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();

            if (empty($radicalsBatch)) {
                break; // No more records to process
            }

            $batchCount++;

            if ($isDryRun) {
                $this->info("\nWould generate UUIDs for " . count($radicalsBatch) . " radicals (batch #{$batchCount})");
            } else {
                // Process each record in the batch
                foreach ($radicalsBatch as $id) {
                    DB::table('japanese_radicals_bank_long')
                        ->where('id', $id)
                        ->update(['uuid' => Str::uuid()->toString()]);
                }

                // Update the progress bar
                $bar->advance(count($radicalsBatch));
                $processed += count($radicalsBatch);

                // Sleep to reduce database load
                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }

            // Check if we've hit the max batches limit
            if ($maxBatches > 0 && $batchCount >= $maxBatches) {
                $this->info("\nReached maximum batch limit ({$maxBatches})");
                break;
            }

        } while (!$isDryRun && !empty($radicalsBatch));

        $bar->finish();

        // Show final status
        $this->info("\n" . str_repeat('=', 50));
        $this->info('Final Status:');
        $this->showCurrentStatus();

        if (!$isDryRun) {
            $this->info("Completed! Generated UUIDs for {$processed} radicals in {$batchCount} batches.");
        } else {
            $this->info("Dry run complete. Would have updated approximately {$totalToUpdate} radicals.");
        }

        return 0;
    }

    /**
     * Show current status of UUID population
     */
    private function showCurrentStatus(): void
    {
        $total = DB::table('japanese_radicals_bank_long')->count();
        $populated = DB::table('japanese_radicals_bank_long')
            ->whereNotNull('uuid')
            ->count();

        $this->info("📊 Total radicals: {$total}");
        $this->info("✅ Populated: {$populated}");
        $this->info("⏳ Remaining: " . ($total - $populated));

        if ($total > 0) {
            $percentage = round(($populated / $total) * 100, 1);
            $this->info("📈 Progress: {$percentage}%");
        }
    }
}

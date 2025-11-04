<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PopulateCommentsUuids extends Command
{
    protected $signature = 'comments:populate-uuids
                           {--batch-size=500 : Number of records per batch}
                           {--delay=100 : Milliseconds to wait between batches}
                           {--max-batches=0 : Maximum batches to process (0 = unlimited)}
                           {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Populate UUID columns in comments table with batching';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $maxBatches = (int) $this->option('max-batches');

        $this->info('Starting comments UUID population...');

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
        $totalToUpdate = DB::table('comments')
            ->whereNull('uuid')
            ->count();

        if ($totalToUpdate === 0) {
            $this->info('No comments need UUID generation. All done!');
            return 0;
        }

        $this->info("Found {$totalToUpdate} comments needing UUIDs");

        // Process in batches
        $bar = $this->output->createProgressBar($totalToUpdate);
        $bar->start();

        $processed = 0;
        $batchCount = 0;

        do {
            $commentsBatch = DB::table('comments')
                ->whereNull('uuid')
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();

            if (empty($commentsBatch)) {
                break;
            }

            $batchCount++;

            if ($isDryRun) {
                $this->info("\nWould generate UUIDs for " . count($commentsBatch) . " comments (batch #{$batchCount})");
            } else {
                // Process each record in the batch
                foreach ($commentsBatch as $id) {
                    DB::table('comments')
                        ->where('id', $id)
                        ->update(['uuid' => Str::uuid()->toString()]);
                }

                // Update the progress bar
                $bar->advance(count($commentsBatch));
                $processed += count($commentsBatch);

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

        } while (!$isDryRun && !empty($commentsBatch));

        $bar->finish();

        // Show final status
        $this->info("\n" . str_repeat('=', 50));
        $this->info('Final Status:');
        $this->showCurrentStatus();

        if (!$isDryRun) {
            $this->info("Completed! Generated UUIDs for {$processed} comments in {$batchCount} batches.");
        } else {
            $this->info("Dry run complete. Would have updated approximately {$totalToUpdate} comments.");
        }

        return 0;
    }

    /**
     * Show current status of UUID population
     */
    private function showCurrentStatus(): void
    {
        $total = DB::table('comments')->count();
        $populated = DB::table('comments')
            ->whereNotNull('uuid')
            ->count();

        $this->info("📊 Total comments: {$total}");
        $this->info("✅ Populated: {$populated}");
        $this->info("⏳ Remaining: " . ($total - $populated));

        if ($total > 0) {
            $percentage = round(($populated / $total) * 100, 1);
            $this->info("📈 Progress: {$percentage}%");
        }
    }
}

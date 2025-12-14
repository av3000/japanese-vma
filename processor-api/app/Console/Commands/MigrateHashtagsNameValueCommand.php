<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateHashtagsNameValueCommand extends Command
{
    protected $signature = 'hashtags:migrate
                            {--dry-run : Simulate the operation without making changes}';

    protected $description = 'Update hashtags with cleaned hashtag names from uniquehashtags';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting hashtags migration...');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Fetch existing hashtags to update
        $hashtagsToUpdate = DB::table('hashtag_entity')
            ->join('uniquehashtags', 'hashtag_entity.hashtag_id', '=', 'uniquehashtags.id')
            ->select('hashtags.id', 'uniquehashtags.content')
            ->get();

        $this->info("Total hashtags to update: {$hashtagsToUpdate->count()}");

        if (!$this->confirm('Do you want to proceed with hashtag name update?')) {
            $this->info('Migration cancelled.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($hashtagsToUpdate->count());
        $progressBar->start();

        $updatedCount = 0;

        foreach ($hashtagsToUpdate as $hashtag) {
            $cleanName = Str::of($hashtag->content)->ltrim('#')->toString();

            if ($isDryRun) {
                $this->line("\nWould update hashtag ID {$hashtag->id}:");
                $this->line("  Original: {$hashtag->content}");
                $this->line("  Cleaned:  {$cleanName}");
            } else {
                DB::table('hashtag_entity')
                    ->where('id', $hashtag->id)
                    ->update([
                        'name' => $cleanName,
                        'updated_at' => now()
                    ]);
            }

            $progressBar->advance();
            $updatedCount++;
        }

        $progressBar->finish();

        $this->info("\n\nMigration Summary:");
        $this->info("Total hashtags updated: {$updatedCount}");

        if (!$isDryRun) {
            $this->info("Hashtags successfully updated!");
        } else {
            $this->info("Dry run complete. No actual changes made.");
        }

        return 0;
    }
}

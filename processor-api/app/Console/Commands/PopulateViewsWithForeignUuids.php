<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Domain\Shared\Enums\ObjectTemplateType;

class PopulateViewsWithForeignUuids extends Command {

    protected $signature = 'views:populate-foreign-uuids
                            {--batch-size=500 : Number of records per batch}
                            {--delay=0 : Delay in milliseconds between batches}
                            {--max-batches=0 : Maximum batches to process (0 = unlimited)}
                            {--dry-run : Simulate the operation without making changes}';
    protected $description = 'Populate foreign UUIDs in views table with batching';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $maxBatches = $this->option('max-batches') !== null ? (int) $this->option('max-batches') : 0;

        $this->info('Starting views foreign UUIDs population...');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->showCurrentStatus();

        if (!$isDryRun && !$this->confirm('Proceed with updating foreign UUIDs?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Get all object templates first (for entity_type_uuid mapping)
        $objectTemplates = DB::table('objecttemplates')
            ->select(['id', 'entity_type_uuid'])
            ->get()
            ->keyBy('id');

        if ($objectTemplates->isEmpty()) {
            $this->error('No object templates found in the database.');
            return 1;
        }

        $totalToUpdate = DB::table('views')
            ->whereNull('real_object_uuid')
            ->orWhereNull('entity_type_uuid')
            ->count();

        if ($totalToUpdate === 0) {
            $this->info('No views need foreign UUID updates. All done!');
            return 0;
        }

        $this->info("Found {$totalToUpdate} views needing foreign UUID updates");
        if ($maxBatches > 0) {
            $this->info("Will process up to {$maxBatches} batches");
        } else {
            $this->info("Will process all records (unlimited batches)");
        }

        $bar = $this->output->createProgressBar($totalToUpdate);
        $bar->start();

        $processed = 0;
        $batchCount = 0;

        do {
            $viewsBatch = DB::table('views')
                ->where(function($query) {
                    $query->whereNull('real_object_uuid')
                          ->orWhereNull('entity_type_uuid');
                })
                ->limit($batchSize)
                ->select(['id', 'template_id', 'real_object_id'])
                ->get();

            if ($viewsBatch->isEmpty()) {
                break;
            }

            $batchCount++;

            if ($isDryRun) {
                $this->info("\nWould update foreign UUIDs for " . count($viewsBatch) . " views (batch #{$batchCount})");

                // Show sample of what would be updated
                foreach ($viewsBatch->take(15) as $view) {
                    // Get the template enum
                    $templateEnum = ObjectTemplateType::tryFromLegacyValue($view->template_id);

                    if (!$templateEnum) {
                        $this->warn("Skipping view ID {$view->id} - unknown template_id: {$view->template_id}");
                        continue;
                    }

                    // Get entity type UUID from object templates
                    $entityTypeUuid = $objectTemplates->get($view->template_id)?->entity_type_uuid;
                    if (!$entityTypeUuid) {
                        $this->warn("No entity type UUID found for template_id {$view->template_id}");
                        continue;
                    }

                    // Get table name
                    $tableName = match($templateEnum) {
                        ObjectTemplateType::ARTICLE => 'articles',
                        ObjectTemplateType::RADICAL => 'japanese_radicals_bank_long',
                        ObjectTemplateType::KANJI => 'japanese_kanji_bank_long',
                        ObjectTemplateType::WORD => 'japanese_word_bank_long',
                        ObjectTemplateType::SENTENCE => 'japanese_tatoeba_sentences',
                        ObjectTemplateType::LIST => 'customlists',
                        ObjectTemplateType::POST => 'posts',
                        ObjectTemplateType::COMMENT => 'comments',
                        default => null,
                    };

                    if (!$tableName) {
                        $this->warn("No table mapping for template_id {$view->template_id}");
                        continue;
                    }

                    // Get the real object UUID from the related table
                    $realObjectUuid = DB::table($tableName)
                        ->where('id', $view->real_object_id)
                        ->value('uuid');

                    if (!$realObjectUuid) {
                        $this->warn("Comment ID {$view->id}: No UUID found for {$tableName} with ID {$view->real_object_id}");
                        continue;
                    }

                    $this->line("Comment ID: {$view->id} would be updated with:");
                    $this->line("  - entity_type_uuid: {$entityTypeUuid} (from template_id {$view->template_id})");
                    $this->line("  - real_object_uuid: {$realObjectUuid} (from {$tableName} ID {$view->real_object_id})");
                }
            } else {
                // Process each record in the batch
                $batchProcessed = 0;
                foreach ($viewsBatch as $view) {
                    $updates = [];

                    // Get entity type UUID from object templates
                    $entityTypeUuid = $objectTemplates->get($view->template_id)?->entity_type_uuid;
                    if ($entityTypeUuid) {
                        $updates['entity_type_uuid'] = $entityTypeUuid;
                    }

                    // Get the template enum
                    $templateEnum = ObjectTemplateType::tryFromLegacyValue($view->template_id);
                    if (!$templateEnum) {
                        continue;
                    }

                    // Get table name
                    $tableName = match($templateEnum) {
                        ObjectTemplateType::ARTICLE => 'articles',
                        ObjectTemplateType::RADICAL => 'japanese_radicals_bank_long',
                        ObjectTemplateType::KANJI => 'japanese_kanji_bank_long',
                        ObjectTemplateType::WORD => 'japanese_word_bank_long',
                        ObjectTemplateType::SENTENCE => 'japanese_tatoeba_sentences',
                        ObjectTemplateType::LIST => 'customlists',
                        ObjectTemplateType::POST => 'posts',
                        ObjectTemplateType::COMMENT => 'views',
                        default => null,
                    };

                    if (!$tableName) {
                        continue;
                    }

                    // Get the UUID from the related table
                    $realObjectUuid = DB::table($tableName)
                        ->where('id', $view->real_object_id)
                        ->value('uuid');

                    if ($realObjectUuid) {
                        $updates['real_object_uuid'] = $realObjectUuid;
                    }

                    // Only update if we have values to update
                    if (!empty($updates)) {
                        DB::table('views')
                            ->where('id', $view->id)
                            ->update($updates);

                        $batchProcessed++;
                    }
                }

                // Update the progress bar
                $bar->advance($batchProcessed);
                $processed += $batchProcessed;

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

        } while (!$isDryRun && !$viewsBatch->isEmpty());

        $bar->finish();

        // Show final status
        $this->info("\n" . str_repeat('=', 50));
        $this->info('Final Status:');
        $this->showCurrentStatus();

        if (!$isDryRun) {
            $this->info("Completed! Updated foreign UUIDs for {$processed} views in {$batchCount} batches.");
        } else {
            $this->info("Dry run complete. Would have updated approximately {$totalToUpdate} views.");
        }

        return 0;
    }

    /**
     * Show current status of UUID population
     */
    private function showCurrentStatus(): void
    {
        $total = DB::table('views')->count();
        $populated = DB::table('views')
            ->whereNotNull('real_object_uuid')
            ->whereNotNull('entity_type_uuid')
            ->count();

        $this->info("📊 Total views: {$total}");
        $this->info("✅ Fully populated: {$populated}");
        $this->info("⏳ Remaining: " . ($total - $populated));

        if ($total > 0) {
            $percentage = round(($populated / $total) * 100, 1);
            $this->info("📈 Progress: {$percentage}%");
        }
    }
}

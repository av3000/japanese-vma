<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Domain\Shared\Enums\ObjectTemplateType;

class PopulateLikesWithForeignUuids extends Command {

    protected $signature = 'likes:populate-foreign-uuids
                            {--batch-size=500 : Number of records per batch}
                            {--delay=0 : Delay in milliseconds between batches}
                            {--max-batches=0 : Maximum batches to process (0 = unlimited)}
                            {--dry-run : Simulate the operation without making changes}';
    protected $description = 'Populate foreign UUIDs in likes table with batching';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $maxBatches = $this->option('max-batches') !== null ? (int) $this->option('max-batches') : 0;

        $this->info('Starting likes foreign UUIDs population...');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->showCurrentStatus();

        if (!$isDryRun && !$this->confirm('Proceed with updating foreign UUIDs?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $objectTemplates = DB::table('objecttemplates')
            ->select(['id', 'entity_type_uuid'])
            ->get()
            ->keyBy('id');

        if ($objectTemplates->isEmpty()) {
            $this->error('No object templates found in the database.');
            return 1;
        }

        $totalToUpdate = DB::table('likes')
            ->whereNull('real_object_uuid')
            ->orWhereNull('entity_type_uuid')
            ->count();

        if ($totalToUpdate === 0) {
            $this->info('No likes need foreign UUID updates. All done!');
            return 0;
        }

        $this->info("Found {$totalToUpdate} likes needing foreign UUID updates");
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
            $likesBatch = DB::table('likes')
                ->where(function($query) {
                    $query->whereNull('real_object_uuid')
                          ->orWhereNull('entity_type_uuid');
                })
                ->limit($batchSize)
                ->select(['id', 'template_id', 'real_object_id'])
                ->get();

            if ($likesBatch->isEmpty()) {
                break;
            }

            $batchCount++;

            if ($isDryRun) {
                $this->info("\nWould update foreign UUIDs for " . count($likesBatch) . " likes (batch #{$batchCount})");

                foreach ($likesBatch->take(15) as $like) {
                    $templateEnum = ObjectTemplateType::tryFromLegacyValue($like->template_id);

                    if (!$templateEnum) {
                        $this->warn("Skipping like ID {$like->id} - unknown template_id: {$like->template_id}");
                        continue;
                    }

                    $entityTypeUuid = $objectTemplates->get($like->template_id)?->entity_type_uuid;
                    if (!$entityTypeUuid) {
                        $this->warn("No entity type UUID found for template_id {$like->template_id}");
                        continue;
                    }

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
                        $this->warn("No table mapping for template_id {$like->template_id}");
                        continue;
                    }

                    $realObjectUuid = DB::table($tableName)
                        ->where('id', $like->real_object_id)
                        ->value('uuid');

                    if (!$realObjectUuid) {
                        $this->warn("Comment ID {$like->id}: No UUID found for {$tableName} with ID {$like->real_object_id}");
                        continue;
                    }

                    $this->line("Comment ID: {$like->id} would be updated with:");
                    $this->line("  - entity_type_uuid: {$entityTypeUuid} (from template_id {$like->template_id})");
                    $this->line("  - real_object_uuid: {$realObjectUuid} (from {$tableName} ID {$like->real_object_id})");
                }
            } else {
                $batchProcessed = 0;
                foreach ($likesBatch as $like) {
                    $updates = [];

                    // Get entity type UUID from object templates
                    $entityTypeUuid = $objectTemplates->get($like->template_id)?->entity_type_uuid;
                    if ($entityTypeUuid) {
                        $updates['entity_type_uuid'] = $entityTypeUuid;
                    }

                    $templateEnum = ObjectTemplateType::tryFromLegacyValue($like->template_id);
                    if (!$templateEnum) {
                        continue;
                    }

                    $tableName = match($templateEnum) {
                        ObjectTemplateType::ARTICLE => 'articles',
                        ObjectTemplateType::RADICAL => 'japanese_radicals_bank_long',
                        ObjectTemplateType::KANJI => 'japanese_kanji_bank_long',
                        ObjectTemplateType::WORD => 'japanese_word_bank_long',
                        ObjectTemplateType::SENTENCE => 'japanese_tatoeba_sentences',
                        ObjectTemplateType::LIST => 'customlists',
                        ObjectTemplateType::POST => 'posts',
                        ObjectTemplateType::COMMENT => 'likes',
                        default => null,
                    };

                    if (!$tableName) {
                        continue;
                    }

                    // Get the UUID from the related table
                    $realObjectUuid = DB::table($tableName)
                        ->where('id', $like->real_object_id)
                        ->value('uuid');

                    if ($realObjectUuid) {
                        $updates['real_object_uuid'] = $realObjectUuid;
                    }

                    if (!empty($updates)) {
                        DB::table('likes')
                            ->where('id', $like->id)
                            ->update($updates);

                        $batchProcessed++;
                    }
                }

                $bar->advance($batchProcessed);
                $processed += $batchProcessed;

                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }

            if ($maxBatches > 0 && $batchCount >= $maxBatches) {
                $this->info("\nReached maximum batch limit ({$maxBatches})");
                break;
            }

        } while (!$isDryRun && !$likesBatch->isEmpty());

        $bar->finish();

        $this->info("\n" . str_repeat('=', 50));
        $this->info('Final Status:');
        $this->showCurrentStatus();

        if (!$isDryRun) {
            $this->info("Completed! Updated foreign UUIDs for {$processed} likes in {$batchCount} batches.");
        } else {
            $this->info("Dry run complete. Would have updated {$totalToUpdate} likes.");
        }

        return 0;
    }

    /**
     * Show current status of UUID population
     */
    private function showCurrentStatus(): void
    {
        $total = DB::table('likes')->count();
        $populated = DB::table('likes')
            ->whereNotNull('real_object_uuid')
            ->whereNotNull('entity_type_uuid')
            ->count();

        $this->info("📊 Total likes: {$total}");
        $this->info("✅ Fully populated: {$populated}");
        $this->info("⏳ Remaining: " . ($total - $populated));

        if ($total > 0) {
            $percentage = round(($populated / $total) * 100, 1);
            $this->info("📈 Progress: {$percentage}%");
        }
    }
}

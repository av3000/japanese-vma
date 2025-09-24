<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateDownloadsUuids extends Command
{
    protected $signature = 'migration:populate-downloads-uuids
                           {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Populate UUID columns in downloads table (one-time migration)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting downloads UUID population...');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Show current status before starting
        $this->showCurrentStatus();

        if (!$isDryRun && !$this->confirm('Proceed with updating UUID columns?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Process each entity type with targeted queries
        $this->processArticleDownloads($isDryRun);
        $this->processListDownloads($isDryRun);

        // Show final status
        $this->info("\n" . str_repeat('=', 50));
        $this->info('Final Status:');
        $this->showCurrentStatus();

        return 0;
    }

    /**
     * Process downloads for articles (template_id = 1)
     */
    private function processArticleDownloads(bool $isDryRun): void
    {
        $this->info("\n📄 Processing Article Downloads...");

        if ($isDryRun) {
            $count = DB::table('downloads')
                ->where('template_id', 1)
                ->whereNull('real_object_uuid')
                ->count();

            $this->info("Would update {$count} article download records");
            return;
        }

        $updated = DB::statement("
            UPDATE downloads
            INNER JOIN articles ON articles.id = downloads.real_object_id
            INNER JOIN objecttemplates ON objecttemplates.id = downloads.template_id
            SET
                downloads.real_object_uuid = articles.uuid,
                downloads.object_template_uuid = objecttemplates.uuid
            WHERE
                downloads.template_id = 1
                AND downloads.real_object_uuid IS NULL
                AND articles.uuid IS NOT NULL
        ");

        $this->info("✅ Updated {$updated} article download records");
    }

    /**
     * Process downloads for lists (template_id = 8)
     */
    private function processListDownloads(bool $isDryRun): void
    {
        $this->info("\n📋 Processing List Downloads...");

        if ($isDryRun) {
            $count = DB::table('downloads')
                ->where('template_id', 8)
                ->whereNull('real_object_uuid')
                ->count();

            $this->info("Would update {$count} list download records");
            return;
        }

        $updated = DB::statement("
            UPDATE downloads
            INNER JOIN customlists ON customlists.id = downloads.real_object_id
            INNER JOIN objecttemplates ON objecttemplates.id = downloads.template_id
            SET
                downloads.real_object_uuid = customlists.uuid,
                downloads.object_template_uuid = objecttemplates.uuid
            WHERE
                downloads.template_id = 8
                AND downloads.real_object_uuid IS NULL
                AND customlists.uuid IS NOT NULL
        ");

        $this->info("✅ Updated {$updated} list download records");
    }

    /**
     * Show current status of UUID population
     */
    private function showCurrentStatus(): void
    {
        $total = DB::table('downloads')->count();
        $populated = DB::table('downloads')
            ->whereNotNull('real_object_uuid')
            ->whereNotNull('object_template_uuid')
            ->count();

        $this->info("📊 Total downloads: {$total}");
        $this->info("✅ Populated: {$populated}");
        $this->info("⏳ Remaining: " . ($total - $populated));

        if ($total > 0) {
            $percentage = round(($populated / $total) * 100, 1);
            $this->info("📈 Progress: {$percentage}%");
        }

        // Show breakdown by entity type
        $this->showBreakdownByType();
    }

        /**
     * Show detailed breakdown by entity type
     */
    private function showBreakdownByType(): void
    {
        $types = [
            1 => 'Articles',
            8 => 'Lists',
        ];

        $this->info("\nBreakdown by type:");
        foreach ($types as $templateId => $name) {
            $total = DB::table('likes')->where('template_id', $templateId)->count();
            $populated = DB::table('likes')
                ->where('template_id', $templateId)
                ->whereNotNull('real_object_uuid')
                ->count();

            if ($total > 0) {
                $this->info("  {$name}: {$populated}/{$total}");
            }
        }
    }
}

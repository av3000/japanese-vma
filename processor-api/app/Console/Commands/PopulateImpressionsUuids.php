<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateImpressionsUuids extends Command
{
    protected $signature = 'migration:populate-impressions-uuids
                           {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Populate UUID columns in likes table (one-time migration)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting likes UUID population...');

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
        $this->processArticleLikes($isDryRun);
        $this->processListLikes($isDryRun);
        $this->processPostLikes($isDryRun);
        $this->processCommentLikes($isDryRun);

        // Show final status
        $this->info("\n" . str_repeat('=', 50));
        $this->info('Final Status:');
        $this->showCurrentStatus();

        return 0;
    }

    /**
     * Process likes for articles (template_id = 1)
     */
    private function processArticleLikes(bool $isDryRun): void
    {
        $this->info("\n📄 Processing Article Likes...");

        if ($isDryRun) {
            $count = DB::table('likes')
                ->where('template_id', 1)
                ->whereNull('real_object_uuid')
                ->count();

            $this->info("Would update {$count} article like records");
            return;
        }

        $updated = DB::update("
            UPDATE likes
            INNER JOIN articles ON articles.id = likes.real_object_id
            INNER JOIN objecttemplates ON objecttemplates.id = likes.template_id
            SET
                likes.real_object_uuid = articles.unique_id,
                likes.object_template_uuid = objecttemplates.uuid
            WHERE
                likes.template_id = 1
                AND likes.real_object_uuid IS NULL
                AND articles.unique_id IS NOT NULL
        ");

        $this->info("✅ Updated {$updated} article like records");
    }

    /**
     * Process likes for custom lists (template_id = 8)
     */
    private function processListLikes(bool $isDryRun): void
    {
        $this->info("\n📋 Processing List Likes...");

        if ($isDryRun) {
            $count = DB::table('likes')
                ->where('template_id', 8)
                ->whereNull('real_object_uuid')
                ->count();

            $this->info("Would update {$count} list like records");
            return;
        }

        $updated = DB::update("
            UPDATE likes
            INNER JOIN customlists ON customlists.id = likes.real_object_id
            INNER JOIN objecttemplates ON objecttemplates.id = likes.template_id
            SET
                likes.real_object_uuid = customlists.uuid,
                likes.object_template_uuid = objecttemplates.uuid
            WHERE
                likes.template_id = 8
                AND likes.real_object_uuid IS NULL
                AND customlists.uuid IS NOT NULL
        ");

        $this->info("✅ Updated {$updated} list like records");
    }

    /**
     * Process likes for posts (template_id = 9)
     */
    private function processPostLikes(bool $isDryRun): void
    {
        $this->info("\n📝 Processing Post Likes...");

        if ($isDryRun) {
            $count = DB::table('likes')
                ->where('template_id', 9)
                ->whereNull('real_object_uuid')
                ->count();

            $this->info("Would update {$count} post like records");
            return;
        }

        $updated = DB::update("
            UPDATE likes
            INNER JOIN posts ON posts.id = likes.real_object_id
            INNER JOIN objecttemplates ON objecttemplates.id = likes.template_id
            SET
                likes.real_object_uuid = posts.uuid,
                likes.object_template_uuid = objecttemplates.uuid
            WHERE
                likes.template_id = 9
                AND likes.real_object_uuid IS NULL
                AND posts.uuid IS NOT NULL
        ");

        $this->info("✅ Updated {$updated} post like records");
    }

    /**
     * Process likes for comments (template_id = 10)
     */
    private function processCommentLikes(bool $isDryRun): void
    {
        $this->info("\n💬 Processing Comment Likes...");

        if ($isDryRun) {
            $count = DB::table('likes')
                ->where('template_id', 10)
                ->whereNull('real_object_uuid')
                ->count();

            $this->info("Would update {$count} comment like records");
            return;
        }

        $updated = DB::update("
            UPDATE likes
            INNER JOIN comments ON comments.id = likes.real_object_id
            INNER JOIN objecttemplates ON objecttemplates.id = likes.template_id
            SET
                likes.real_object_uuid = comments.uuid,
                likes.object_template_uuid = objecttemplates.uuid
            WHERE
                likes.template_id = 10
                AND likes.real_object_uuid IS NULL
                AND comments.uuid IS NOT NULL
        ");

        $this->info("✅ Updated {$updated} comment like records");
    }

    /**
     * Show current status of UUID population
     */
    private function showCurrentStatus(): void
    {
        $total = DB::table('likes')->count();
        $populated = DB::table('likes')
            ->whereNotNull('real_object_uuid')
            ->whereNotNull('object_template_uuid')
            ->count();

        $this->info("📊 Total likes: {$total}");
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
            9 => 'Posts',
            10 => 'Comments'
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Domain\Shared\Enums\ObjectTemplateType;

class FixEntityUuids extends Command
{
    protected $signature = 'migration:fix-entity-uuids
                           {--dry-run : Show changes without updating}';

    protected $description = 'Fix per-instance UUIDs for entities and update dependent tables';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting UUID fix for entities...');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Process entities
        $this->processArticles($isDryRun);
        $this->processCustomLists($isDryRun);

        $this->info('✅ Done.');

        return 0;
    }

    private function processArticles(bool $isDryRun): void
    {
        $this->info("\n📄 Articles:");

        $articles = DB::table('articles')->get();

        foreach ($articles as $article) {
            $perRowUuid = Str::uuid()->toString();

            if ($isDryRun) {
                $this->line("Would set uuid={$perRowUuid} for article id={$article->id}");
            } else {
                DB::table('articles')->where('id', $article->id)->update([
                    'uuid' => $perRowUuid,
                    'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
                ]);
            }
        }

        // Update dependent downloads
        $this->info("Updating downloads for articles...");
        $downloads = DB::table('downloads')->where('template_id', 1)->get();

        foreach ($downloads as $d) {
            if ($isDryRun) {
                $this->line("Would update download id={$d->id} real_object_uuid=article uuid");
            } else {
                $uuid = DB::table('articles')->where('id', $d->real_object_id)->value('uuid');
                DB::table('downloads')->where('id', $d->id)->update([
                    'real_object_uuid' => $uuid,
                    'object_template_uuid' => ObjectTemplateType::ARTICLE->value,
                ]);
            }
        }
    }

    private function processCustomLists(bool $isDryRun): void
    {
        $this->info("\n📋 Custom Lists:");

        $lists = DB::table('customlists')->get();

        foreach ($lists as $list) {
            $perRowUuid = Str::uuid()->toString();

            if ($isDryRun) {
                $this->line("Would set uuid={$perRowUuid} for customlist id={$list->id}");
            } else {
                DB::table('customlists')->where('id', $list->id)->update([
                    'uuid' => $perRowUuid,
                    'entity_type_uuid' => ObjectTemplateType::LIST->value,
                ]);
            }
        }

        // Update dependent downloads
        $this->info("Updating downloads for custom lists...");
        $downloads = DB::table('downloads')->where('template_id', 8)->get();

        foreach ($downloads as $d) {
            if ($isDryRun) {
                $this->line("Would update download id={$d->id} real_object_uuid=customlist uuid");
            } else {
                $uuid = DB::table('customlists')->where('id', $d->real_object_id)->value('uuid');
                DB::table('downloads')->where('id', $d->id)->update([
                    'real_object_uuid' => $uuid,
                    'object_template_uuid' => ObjectTemplateType::LIST->value,
                ]);
            }
        }
    }
}

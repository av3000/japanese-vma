<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Report-first companion to the normalised-key migration: lists tags that differ only in case
 * or character width. Merging happens in the admin panel (Community > Hashtags).
 */
class ListDuplicateHashtags extends Command
{
    protected $signature = 'hashtags:duplicates';

    protected $description = 'List hashtags that differ only in case or character width, for merging in the admin panel';

    public function handle(): int
    {
        $groups = DB::table('uniquehashtags')
            ->select('normalized_content')
            ->selectRaw("string_agg(content || ' (#' || id || ')', ', ' ORDER BY id) AS tags")
            ->selectRaw('COUNT(*) AS tag_count')
            ->whereNotNull('normalized_content')
            ->groupBy('normalized_content')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('normalized_content')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate hashtags.');

            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Tags', 'Count'],
            $groups->map(static fn (object $group): array => [$group->normalized_content, $group->tags, $group->tag_count])->all(),
        );
        $this->warn("{$groups->count()} duplicate group(s). Merge them under Community > Hashtags before the key becomes unique.");

        return self::SUCCESS;
    }
}

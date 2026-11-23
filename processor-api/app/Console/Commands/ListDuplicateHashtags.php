<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Engagement\DTOs\HashtagDuplicateGroupDTO;
use Illuminate\Console\Command;

/**
 * Report-first companion to the normalised-key migration: lists tags that differ only in case
 * or character width. Merging happens in the admin panel (Community > Hashtags).
 */
class ListDuplicateHashtags extends Command
{
    protected $signature = 'hashtags:duplicates';

    protected $description = 'List hashtags that differ only in case or character width, for merging in the admin panel';

    public function handle(HashtagRepositoryInterface $hashtags): int
    {
        $groups = $hashtags->findDuplicateGroups();

        if ($groups === []) {
            $this->info('No duplicate hashtags.');

            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Tags', 'Count'],
            array_map(static fn (HashtagDuplicateGroupDTO $group): array => [
                $group->key,
                implode(', ', array_map(static fn (array $tag): string => "{$tag['content']} (#{$tag['id']})", $group->tags)),
                count($group->tags),
            ], $groups),
        );
        $this->warn(count($groups).' duplicate group(s). Merge them under Community > Hashtags before the key becomes unique.');

        return self::SUCCESS;
    }
}

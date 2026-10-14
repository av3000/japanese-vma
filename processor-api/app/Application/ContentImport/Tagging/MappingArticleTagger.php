<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Tagging;

use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\Repositories\SourceTagMappingRepositoryInterface;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\Enums\SourceTagKind;
use Illuminate\Support\Facades\Log;

/**
 * Tags an Imported Article from the source's own labels through the `source_tag_mappings`
 * allow-list: topics first, because they are specific, then genres, deduplicated and capped.
 *
 * A topic with no mapping is dropped and logged once per run, so the list can grow from what
 * the source actually publishes.
 */
class MappingArticleTagger implements ArticleTaggerInterface
{
    /** @var array<int, array<value-of<SourceTagKind>, array<string, string>>> */
    private array $mappingsBySource = [];

    /** @var array<string, true> */
    private array $reportedUnmapped = [];

    public function __construct(
        private readonly SourceTagMappingRepositoryInterface $mappings,
    ) {
    }

    public function tagsFor(ContentSourceDTO $source, ExternalArticle $article): array
    {
        $mappings = $this->mappingsBySource[$source->id] ??= $this->mappings->forSource($source->id);
        $topicMap = $mappings[SourceTagKind::Topic->value] ?? [];
        $genreMap = $mappings[SourceTagKind::Genre->value] ?? [];
        $tags = [];

        foreach ($article->topics as $topicKey => $topicName) {
            $topicKey = (string) $topicKey;

            if (isset($topicMap[$topicKey])) {
                $tags[] = $topicMap[$topicKey];
            } else {
                $this->reportUnmapped($source, $topicKey, $topicName);
            }
        }

        foreach ($article->genres as $genre) {
            if (isset($genreMap[$genre])) {
                $tags[] = $genreMap[$genre];
            }
        }

        return array_slice(array_values(array_unique($tags)), 0, self::MAX_TAGS);
    }

    private function reportUnmapped(ContentSourceDTO $source, string $key, string $name): void
    {
        $marker = "{$source->id}:{$key}";

        if (isset($this->reportedUnmapped[$marker])) {
            return;
        }

        $this->reportedUnmapped[$marker] = true;

        Log::info('Content import topic has no tag mapping', [
            'source' => $source->key,
            'topic_key' => $key,
            'topic_name' => $name,
        ]);
    }
}

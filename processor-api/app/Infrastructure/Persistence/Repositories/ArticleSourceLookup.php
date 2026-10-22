<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Articles\ValueObjects\ArticleSource;
use App\Infrastructure\Persistence\Models\ContentSource;

/**
 * The Content Source an Imported Article credits, by id. The mapper asks this instead of every
 * article query eager-loading `contentSource`, so a new query cannot forget it and silently
 * drop the attribution.
 *
 * `content_sources` holds a handful of rows, so they are loaded together on first use: one
 * query per request at most, and none for a page of user articles. The copy is refreshed
 * after a few minutes because this lookup outlives a request in a queue worker.
 */
class ArticleSourceLookup
{
    private const REFRESH_AFTER_SECONDS = 300;

    /** @var array<int, ArticleSource>|null */
    private ?array $sourcesById = null;

    private float $loadedAt = 0.0;

    public function find(int $contentSourceId): ?ArticleSource
    {
        if ($this->sourcesById === null || microtime(true) - $this->loadedAt > self::REFRESH_AFTER_SECONDS) {
            $this->sourcesById = $this->load();
            $this->loadedAt = microtime(true);
        }

        return $this->sourcesById[$contentSourceId] ?? null;
    }

    /**
     * @return array<int, ArticleSource>
     */
    private function load(): array
    {
        $sources = [];

        foreach (ContentSource::query()->get(['id', 'key', 'name', 'homepage_url']) as $source) {
            $sources[$source->id] = new ArticleSource($source->key, $source->name, $source->homepage_url);
        }

        return $sources;
    }
}

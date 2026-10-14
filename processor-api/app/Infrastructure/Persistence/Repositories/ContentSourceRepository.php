<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Infrastructure\Persistence\Models\ContentSource;

class ContentSourceRepository implements ContentSourceRepositoryInterface
{
    public function findByKey(string $key): ?ContentSourceDTO
    {
        $source = ContentSource::query()->where('key', $key)->first();

        return $source === null ? null : new ContentSourceDTO(
            id: $source->id,
            key: $source->key,
            name: $source->name,
            homepageUrl: $source->homepage_url,
            enabled: $source->enabled,
        );
    }

    public function enabledKeys(): array
    {
        return ContentSource::query()->where('enabled', true)->orderBy('key')->pluck('key')->all();
    }
}

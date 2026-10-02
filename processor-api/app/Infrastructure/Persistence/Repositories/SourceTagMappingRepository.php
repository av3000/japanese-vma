<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\ContentImport\Interfaces\Repositories\SourceTagMappingRepositoryInterface;
use App\Domain\ContentImport\Enums\SourceTagKind;
use App\Infrastructure\Persistence\Models\SourceTagMapping;

class SourceTagMappingRepository implements SourceTagMappingRepositoryInterface
{
    public function forSource(int $contentSourceId): array
    {
        $mappings = [SourceTagKind::Genre->value => [], SourceTagKind::Topic->value => []];

        SourceTagMapping::query()
            ->where('content_source_id', $contentSourceId)
            ->get(['kind', 'external_key', 'hashtag'])
            ->each(function (SourceTagMapping $mapping) use (&$mappings): void {
                $mappings[$mapping->kind->value][$mapping->external_key] = $mapping->hashtag;
            });

        return $mappings;
    }
}

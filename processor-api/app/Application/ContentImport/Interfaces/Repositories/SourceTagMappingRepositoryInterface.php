<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces\Repositories;

use App\Domain\ContentImport\Enums\SourceTagKind;

interface SourceTagMappingRepositoryInterface
{
    /**
     * Every mapping for a source, as kind => [external key => hashtag].
     *
     * @return array<value-of<SourceTagKind>, array<string, string>>
     */
    public function forSource(int $contentSourceId): array;
}

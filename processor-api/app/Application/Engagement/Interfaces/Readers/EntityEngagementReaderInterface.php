<?php

declare(strict_types=1);

namespace App\Application\Engagement\Interfaces\Readers;

use App\Domain\Engagement\DTOs\EntityEngagementCountsDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Engagement and tags of one entity, for detail views such as the admin panel's article and
 * catalogue pages.
 */
interface EntityEngagementReaderInterface
{
    /**
     * Views, likes, comments and downloads, read together in one query.
     */
    public function counts(ObjectTemplateType $type, int $entityId): EntityEngagementCountsDTO;

    /**
     * Live tag names on the entity (removed links left out), alphabetically.
     *
     * @return list<string>
     */
    public function hashtagNames(ObjectTemplateType $type, int $entityId): array;
}

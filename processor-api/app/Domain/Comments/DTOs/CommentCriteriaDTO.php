<?php

namespace App\Domain\Comments\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\Pagination;

readonly class CommentCriteriaDTO
{
    /**
     * `$sortBy` and `$sortDir` arrive already narrowed to the request whitelist.
     */
    public function __construct(
        public int $entityId,
        public ObjectTemplateType $entityType,
        public ?Pagination $pagination = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
    ) {
    }
}

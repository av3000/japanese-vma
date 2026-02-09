<?php

namespace App\Domain\Comments\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;

readonly class CommentCriteriaDTO
{
    public function __construct(
        public ?string $entityId = null,
        public ObjectTemplateType $entityType,
        public ?SearchTerm $search = null,
        public ?Pagination $pagination = null,
        public bool $include_replies = false,
        public bool $include_author = false,
    ) {}
}

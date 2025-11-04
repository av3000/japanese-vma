<?php
namespace App\Domain\Comments\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\CommentSearchTerm;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\User;

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

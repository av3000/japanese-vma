<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;

interface PostReadServiceInterface
{
    /**
     * @return Result Success payload: PostListResultDTO
     */
    public function find(PostQueryCriteria $criteria): Result;

    /**
     * Resolves a UUID (canonical) or a positive legacy integer, records the
     * detail view for authenticated viewers, and enriches the Post.
     *
     * @return Result Success payload: PostDetailResultDTO
     */
    public function findByIdentifier(string $identifier, Viewer $viewer): Result;
}

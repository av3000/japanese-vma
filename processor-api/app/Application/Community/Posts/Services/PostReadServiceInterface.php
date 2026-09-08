<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Domain\Community\Posts\DTOs\PostDetailResultDTO;
use App\Domain\Community\Posts\Models\Post;
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

    /**
     * Enriches an already-resolved Post with stats and hashtags, with no view
     * side effect. The write slice returns the canonical detail shape after a
     * create or update, where recording a view for the author would be wrong.
     */
    public function describe(Post $post): PostDetailResultDTO;
}

<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Community\Posts\DTOs\PostCreateDTO;
use App\Domain\Community\Posts\DTOs\PostUpdateDTO;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;

interface PostWriteServiceInterface
{
    /**
     * @return Result Success payload: PostDetailResultDTO
     */
    public function create(PostCreateDTO $dto, AuthenticatedUser $actor, Viewer $viewer): Result;

    /**
     * Owner-only. Admins moderate through lock and delete instead.
     *
     * @return Result Success payload: PostDetailResultDTO
     */
    public function update(EntityId $uuid, PostUpdateDTO $dto, AuthenticatedUser $actor): Result;

    /**
     * Owner or admin.
     *
     * @return Result Success payload: null
     */
    public function delete(EntityId $uuid, AuthenticatedUser $actor): Result;

    /**
     * Admin-only, idempotent.
     *
     * @return Result Success payload: Post
     */
    public function setLocked(EntityId $uuid, bool $locked, AuthenticatedUser $actor): Result;
}

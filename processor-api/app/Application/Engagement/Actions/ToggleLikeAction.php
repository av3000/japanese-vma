<?php

declare(strict_types=1);

namespace App\Application\Engagement\Actions;

use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeTargetRepositoryInterface;
use App\Domain\Engagement\DTOs\LikeCreateDTO;
use App\Domain\Engagement\DTOs\LikeFilterDTO;
use App\Domain\Engagement\DTOs\LikeToggleDTO;
use App\Domain\Engagement\DTOs\LikeToggleResult;
use App\Domain\Engagement\Errors\LikeErrors;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;

/**
 * The single generic like toggle for every supported target.
 *
 * Ordering matters twice here:
 *
 * 1. Visibility is proven before anything is written, so a private target cannot be
 *    liked into existence and cannot be probed through a difference in responses.
 * 2. The toggle deletes first and inserts only when nothing was deleted. That reads
 *    the current state and mutates it in one statement, so no window opens between
 *    "is it liked" and "flip it" - the likes_user_target_unique index closes the
 *    remaining insert race, and createIfAbsent absorbs the loser.
 */
class ToggleLikeAction
{
    public function __construct(
        private LikeRepositoryInterface $likeRepository,
        private LikeTargetRepositoryInterface $likeTargetRepository,
    ) {
    }

    public function execute(LikeToggleDTO $dto): Result
    {
        $userId = $dto->userId->value();
        $templateId = $dto->target->legacyId();

        if (! $this->likeTargetRepository->isVisibleTo($dto->target, $dto->entityId, $userId)) {
            return Result::failure(LikeErrors::targetNotFound());
        }

        $toggled = DB::transaction(function () use ($dto, $userId, $templateId): LikeToggleResult {
            $removed = $this->likeRepository->deleteForUserTarget($userId, $templateId, $dto->entityId);
            $isLiked = $removed === 0;

            if ($isLiked) {
                $this->likeRepository->createIfAbsent(new LikeCreateDTO(
                    userId: $userId,
                    templateId: $templateId,
                    realObjectId: $dto->entityId,
                ));
            }

            return new LikeToggleResult(
                isLiked: $isLiked,
                likesCount: $this->likeRepository->countByFilter(new LikeFilterDTO(
                    entityId: $dto->entityId,
                    objectType: $dto->target->objectTemplateType(),
                )),
            );
        });

        return Result::success($toggled);
    }
}

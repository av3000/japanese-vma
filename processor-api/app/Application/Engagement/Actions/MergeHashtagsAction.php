<?php

declare(strict_types=1);

namespace App\Application\Engagement\Actions;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Policies\HashtagManagementPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Engagement\DTOs\HashtagMergeResultDTO;
use App\Domain\Engagement\Errors\HashtagErrors;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Folds one tag into another in a single transaction: links move, duplicates are retired, the
 * source tag is deleted. Any failure rolls back, leaving both tags as they were. The activity row
 * keeps the source name and every moved link id, so the merge can be undone by hand.
 */
final class MergeHashtagsAction
{
    public function __construct(
        private readonly HashtagRepositoryInterface $hashtags,
        private readonly ActivityRecorderInterface $activityRecorder,
        private readonly HashtagManagementPolicy $policy,
    ) {
    }

    /**
     * @return Result Success payload: HashtagMergeResultDTO
     */
    public function execute(int $sourceId, int $targetId, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(HashtagErrors::manageAccessDenied());
        }

        if ($sourceId === $targetId) {
            return Result::failure(HashtagErrors::mergeIntoItself());
        }

        foreach ([$sourceId, $targetId] as $hashtagId) {
            if ($this->hashtags->findTag($hashtagId) === null) {
                return Result::failure(HashtagErrors::notFound($hashtagId));
            }
        }

        try {
            $merged = DB::transaction(function () use ($sourceId, $targetId, $actor): HashtagMergeResultDTO {
                $merged = $this->hashtags->merge($sourceId, $targetId);

                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    ActivityAction::HashtagMerged,
                    ActivitySubjectType::Hashtag,
                    (string) $targetId,
                    $merged->target->content,
                    before: ['content' => $merged->source->content],
                    after: ['content' => $merged->target->content],
                    context: [
                        'source_id' => $merged->source->id,
                        'source_name' => $merged->source->content,
                        'moved_link_ids' => $merged->movedLinkIds,
                        'retired_link_ids' => $merged->retiredLinkIds,
                    ],
                ));

                return $merged;
            });
        } catch (Throwable $exception) {
            Log::error('Hashtag merge failed', [
                'source_id' => $sourceId,
                'target_id' => $targetId,
                'user_id' => $actor->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(HashtagErrors::mergeFailed());
        }

        return Result::success($merged);
    }
}

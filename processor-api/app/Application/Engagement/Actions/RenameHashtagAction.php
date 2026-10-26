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
use App\Domain\Engagement\DTOs\HashtagTagDTO;
use App\Domain\Engagement\Errors\HashtagErrors;
use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Renames a tag everywhere it is used. A name that matches another tag once case and width are
 * ignored is refused: that is a merge, not a rename.
 */
final class RenameHashtagAction
{
    public function __construct(
        private readonly HashtagRepositoryInterface $hashtags,
        private readonly ActivityRecorderInterface $activityRecorder,
        private readonly HashtagManagementPolicy $policy,
    ) {
    }

    /**
     * @return Result Success payload: HashtagTagDTO
     */
    public function execute(int $hashtagId, string $newName, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(HashtagErrors::manageAccessDenied());
        }

        $current = $this->hashtags->findTag($hashtagId);

        if ($current === null) {
            return Result::failure(HashtagErrors::notFound($hashtagId));
        }

        try {
            $name = HashtagName::from($newName);
        } catch (InvalidArgumentException $exception) {
            return Result::failure(HashtagErrors::invalidName($exception->getMessage()));
        }

        if ($name->display === $current->content) {
            return Result::success($current);
        }

        $clash = $this->hashtags->findTagByKey($name->key, exceptId: $hashtagId);

        if ($clash !== null) {
            return Result::failure(HashtagErrors::nameTaken($clash->content));
        }

        $renamed = DB::transaction(function () use ($hashtagId, $name, $current, $actor): HashtagTagDTO {
            $renamed = $this->hashtags->rename($hashtagId, $name);

            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::HashtagRenamed,
                ActivitySubjectType::Hashtag,
                (string) $hashtagId,
                $renamed->content,
                before: ['content' => $current->content],
                after: ['content' => $renamed->content],
            ));

            return $renamed;
        });

        return Result::success($renamed);
    }
}

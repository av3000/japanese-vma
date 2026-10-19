<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Sentences\Actions\CleanupSentenceDependenciesAction;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Policies\SentenceModerationPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\JapaneseMaterial\Sentences\Errors\SentenceErrors;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SentenceModerationService implements SentenceModerationServiceInterface
{
    public function __construct(
        private readonly SentenceRepositoryInterface $sentenceRepository,
        private readonly SentenceModerationPolicy $moderationPolicy,
        private readonly CleanupSentenceDependenciesAction $cleanupSentenceDependencies,
        private readonly ActivityRecorderInterface $activityRecorder,
    ) {
    }

    public function remove(EntityId $uuid, AuthenticatedUser $actor): Result
    {
        if (! $this->moderationPolicy->canModerate($actor)) {
            return Result::failure(SentenceErrors::accessDenied($uuid->value()));
        }

        $sentence = $this->sentenceRepository->findByUuid($uuid);

        if ($sentence === null) {
            return Result::failure(SentenceErrors::notFound($uuid->value()));
        }

        if (! $this->moderationPolicy->canRemove($actor, $sentence)) {
            return Result::failure(SentenceErrors::immutableImported($uuid->value()));
        }

        try {
            DB::transaction(function () use ($sentence, $actor): void {
                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    ActivityAction::SentenceDeleted,
                    ActivitySubjectType::Sentence,
                    $sentence->getUuid()->value(),
                    mb_substr($sentence->getContent(), 0, 120),
                    before: ['content' => $sentence->getContent(), 'author_id' => $sentence->getUserId()],
                ));

                // Same two steps as SentenceService::delete; the dependents live in the action.
                $this->cleanupSentenceDependencies->execute($sentence->getIdValue());
                $this->sentenceRepository->delete($sentence->getIdValue());
            });
        } catch (Throwable $exception) {
            Log::error('Sentence moderation removal failed', [
                'user_id' => $actor->id->value(),
                'sentence_uuid' => $uuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(SentenceErrors::deletionFailed());
        }

        return Result::success();
    }
}

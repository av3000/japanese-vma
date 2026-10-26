<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Processing;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Application\Articles\Policies\ArticleProcessingPolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\ProcessingThresholds;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Runs article content processing again for the article's current content, exactly as an edit
 * would: the processing row is reset to pending in a transaction, and the same job class with the
 * same constructor arguments as ArticleService is dispatched after commit. The worker on the GCP
 * VM therefore sees nothing it does not already handle.
 *
 * A row that is still pending or processing and has reported progress within the stale threshold
 * is refused unless forced, so a retry does not race a healthy run. The actor needs
 * `operations.manage` (ArticleProcessingPolicy).
 */
final class RequeueArticleProcessingAction
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly ArticleProcessingStateReaderInterface $states,
        private readonly ProcessingStateServiceInterface $processingStates,
        private readonly ActivityRecorderInterface $activityRecorder,
        private readonly ArticleProcessingPolicy $policy,
    ) {
    }

    /**
     * @return Result Success payload: ProcessingStateDTO, the reset row
     */
    public function execute(EntityId $articleUuid, AuthenticatedUser $actor, bool $force = false): Result
    {
        if (! $this->policy->canRequeue($actor)) {
            return Result::failure(ArticleErrors::processingRequeueAccessDenied());
        }

        $source = $this->articles->findProcessingSource($articleUuid);

        if ($source === null) {
            return Result::failure(ArticleErrors::notFound($articleUuid->value()));
        }

        $current = $this->states->currentState($articleUuid->value());

        if (! $force && $current !== null && self::isRunning($current)) {
            return Result::failure(ArticleErrors::processingStillRunning($articleUuid->value()));
        }

        /** @var ProcessingStateDTO $reset */
        $reset = DB::transaction(function () use ($articleUuid, $actor, $force, $source, $current): ProcessingStateDTO {
            $reset = $this->processingStates->startOrReset(
                ProcessingEntityType::Article,
                $articleUuid,
                ProcessArticleContentJob::TASK,
                $source->contentVersion,
            );

            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::ArticleReprocessRequested,
                ActivitySubjectType::Article,
                $articleUuid->value(),
                $source->titleJp,
                context: array_filter([
                    'previous_status' => $current?->status->value,
                    'content_version' => $source->contentVersion,
                    'forced' => $force ?: null,
                ], static fn (mixed $value): bool => $value !== null),
            ));

            return $reset;
        });

        ProcessArticleContentJob::dispatch($articleUuid->value(), $source->contentVersion);

        return Result::success($reset);
    }

    public static function isRunning(ProcessingStateDTO $state): bool
    {
        if ($state->status->isTerminal()) {
            return false;
        }

        $lastProgress = $state->updatedAt ?? $state->createdAt;

        return $lastProgress !== null
            && CarbonImmutable::instance($lastProgress)->greaterThan(
                CarbonImmutable::now()->subSeconds(ProcessingThresholds::STALE_AFTER_SECONDS),
            );
    }
}

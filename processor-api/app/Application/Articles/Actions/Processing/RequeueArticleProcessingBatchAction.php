<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Processing;

use App\Application\Articles\Policies\ArticleProcessingPolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Processing\DTOs\ProcessingRetryBatchDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

/**
 * Retries a selection of processing runs, one RequeueArticleProcessingAction each.
 *
 * A selection over MAX_BATCH is refused as a whole rather than cut: the operator cannot see which
 * runs "the first 50" would be, and the message says exactly what to do (#607). Runs that cannot be
 * retried, or are still reporting progress, are skipped and reported as such, not as failures.
 */
final class RequeueArticleProcessingBatchAction
{
    public const MAX_BATCH = 50;

    public function __construct(
        private readonly RequeueArticleProcessingAction $requeue,
        private readonly ArticleProcessingPolicy $policy,
    ) {
    }

    /**
     * @param list<array{entityType: ProcessingEntityType, entityId: string, label: string}> $runs
     *
     * @return Result Success payload: ProcessingRetryBatchDTO
     */
    public function execute(array $runs, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canRequeue($actor)) {
            return Result::failure(ArticleErrors::processingRequeueAccessDenied());
        }

        if (count($runs) > self::MAX_BATCH) {
            return Result::failure(ArticleErrors::retryBatchTooLarge(count($runs), self::MAX_BATCH));
        }

        $queued = 0;
        $failures = [];
        $skipped = [];

        foreach ($runs as ['entityType' => $type, 'entityId' => $entityId, 'label' => $label]) {
            if (! $type->isRetryable()) {
                $skipped[] = "{$label}: only article processing can be retried";

                continue;
            }

            $result = $this->requeue->execute(new EntityId($entityId), $actor);

            if ($result->isSuccess()) {
                $queued++;

                continue;
            }

            $error = $result->getError();

            if ($error->code === ArticleErrors::processingStillRunning($entityId)->code) {
                $skipped[] = "{$label}: still reporting progress";
            } else {
                $failures[] = "{$label}: ".($error->errorMessage ?? $error->description);
            }
        }

        return Result::success(new ProcessingRetryBatchDTO($queued, $failures, $skipped));
    }
}

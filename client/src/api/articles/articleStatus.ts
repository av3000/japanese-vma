import { ArticleStatus } from '@/api/generated/model/articleStatus';

/**
 * Named vocabulary for the generated `ArticleStatus` enum, which orval emits as
 * `NUMBER_0 .. NUMBER_4`. `satisfies` keeps the values pinned to the contract: if the
 * backend enum loses a member, this stops compiling.
 *
 * Mirrors `App\Domain\Shared\Enums\ArticleStatus`.
 */
export const ARTICLE_STATUS = {
	PENDING: ArticleStatus.NUMBER_0,
	PROCESSED: ArticleStatus.NUMBER_1,
	REVIEWING: ArticleStatus.NUMBER_2,
	REJECTED: ArticleStatus.NUMBER_3,
	APPROVED: ArticleStatus.NUMBER_4,
} as const satisfies Record<string, ArticleStatus>;

/**
 * "Awaiting review": the statuses `ArticleRepository::findModerationQueue` selects on. The
 * moderation queue, the dashboard's approval filter and its header count all read this one list.
 */
export const AWAITING_REVIEW_STATUSES: readonly ArticleStatus[] = [ARTICLE_STATUS.PENDING, ARTICLE_STATUS.REVIEWING];

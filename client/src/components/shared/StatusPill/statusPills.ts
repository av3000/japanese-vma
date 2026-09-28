import { ARTICLE_STATUS } from '@/api/articles/moderation';
import type { ArticleStatus } from '@/api/generated/model/articleStatus';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { StatusPillIcon, StatusTone } from './';

export interface ArticleStatusPill {
	tone: StatusTone;
	label: string;
}

export interface ProcessingStatusPill extends ArticleStatusPill {
	icon: StatusPillIcon;
}

// Keyed by the generated enum, so a status the backend adds stops this compiling until it has a pill.
const ARTICLE_STATUS_PILLS: Record<ArticleStatus, ArticleStatusPill> = {
	[ARTICLE_STATUS.PENDING]: { tone: 'warning', label: 'Approval: Pending' },
	[ARTICLE_STATUS.PROCESSED]: { tone: 'neutral', label: 'Approval: Processed' },
	[ARTICLE_STATUS.REVIEWING]: { tone: 'info', label: 'Approval: Reviewing' },
	[ARTICLE_STATUS.REJECTED]: { tone: 'danger', label: 'Approval: Rejected' },
	[ARTICLE_STATUS.APPROVED]: { tone: 'success', label: 'Approval: Approved' },
};

const PENDING_ARTICLE_PILL = ARTICLE_STATUS_PILLS[ARTICLE_STATUS.PENDING];

/**
 * Moderation status as a pill. Resources type `status` as a plain number, so a value outside the
 * enum falls back to the pending pill, as the old `ArticleStatus` badge did.
 */
export const articleStatusPill = (status: ArticleStatus | number): ArticleStatusPill =>
	ARTICLE_STATUS_PILLS[status as ArticleStatus] ?? PENDING_ARTICLE_PILL;

const PROCESSING_STATUS_PILLS: Record<ProcessingStatus, ProcessingStatusPill> = {
	[ProcessingStatus.pending]: { tone: 'warning', label: 'Pending', icon: 'minusSolid' },
	[ProcessingStatus.processing]: { tone: 'warning', label: 'Processing', icon: 'spinner' },
	[ProcessingStatus.completed]: { tone: 'success', label: 'Completed', icon: 'checkSolid' },
	[ProcessingStatus.failed]: { tone: 'danger', label: 'Failed', icon: 'removeSolid' },
	// Terminal and normally hidden by callers; kept so every status has a rendering.
	[ProcessingStatus.superseded]: { tone: 'neutral', label: 'Superseded', icon: 'minusSolid' },
};

export const processingStatusPill = (status: ProcessingStatus): ProcessingStatusPill => PROCESSING_STATUS_PILLS[status];

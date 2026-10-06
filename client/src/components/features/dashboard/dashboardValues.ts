import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import type { ArticleStatus } from '@/api/generated/model/articleStatus';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import type { IconName } from '@/components/shared/Icon';
import { articleStatusPill, type ArticleStatusPill } from '@/components/shared/StatusPill';

/**
 * Typed display values for the dashboard tables, so no cell checks raw numbers or strings in JSX.
 */

/** Mirrors `App\Domain\Shared\Enums\PublicityStatus`; resources send it as a plain number. */
export const PUBLICITY = {
	PRIVATE: 0,
	PUBLIC: 1,
} as const;

export interface VisibilityDisplay {
	label: 'Public' | 'Private';
	icon: IconName;
}

const PUBLIC_VISIBILITY: VisibilityDisplay = { label: 'Public', icon: 'eyeRegular' };
const PRIVATE_VISIBILITY: VisibilityDisplay = { label: 'Private', icon: 'lockSolid' };

/** Anything that is not explicitly public reads as private: the safer label for an owner. */
export const visibilityDisplay = (publicity: number): VisibilityDisplay =>
	publicity === PUBLICITY.PUBLIC ? PUBLIC_VISIBILITY : PRIVATE_VISIBILITY;

/**
 * Approval status as a pill for a column already headed "Approval", so the label drops the
 * "Approval:" prefix the shared `articleStatusPill` carries. The tone stays the shared one; keyed
 * by the generated enum, so a new backend status stops this compiling until it has a label.
 */
const APPROVAL_LABELS: Record<ArticleStatus, string> = {
	[ARTICLE_STATUS.PENDING]: 'Pending',
	[ARTICLE_STATUS.PROCESSED]: 'Processed',
	[ARTICLE_STATUS.REVIEWING]: 'Reviewing',
	[ARTICLE_STATUS.REJECTED]: 'Rejected',
	[ARTICLE_STATUS.APPROVED]: 'Approved',
};

export const approvalColumnPill = (status: number): ArticleStatusPill => ({
	...articleStatusPill(status),
	label: APPROVAL_LABELS[status as ArticleStatus] ?? APPROVAL_LABELS[ARTICLE_STATUS.PENDING],
});

/**
 * The next step under the approval pill, when the owner has one. There is no reviewer note yet
 * (#184 owns it), so a rejected article only points at Edit.
 */
export const approvalHint = (status: number): string | null =>
	status === ARTICLE_STATUS.REJECTED ? 'Edit and resubmit' : null;

const VISIBLE_PROCESSING: ReadonlySet<ProcessingStatus> = new Set([
	ProcessingStatus.pending,
	ProcessingStatus.processing,
	ProcessingStatus.failed,
]);

/**
 * The processing state worth a pill on the dashboard: only while it still needs the owner's
 * attention. Completed and superseded runs, and articles with no run at all, show nothing.
 */
export const visibleProcessingStatus = (
	processing: Pick<ProcessingStatusResource, 'status'> | null | undefined,
): ProcessingStatus | null => (processing && VISIBLE_PROCESSING.has(processing.status) ? processing.status : null);

/** Engagement counts arrive as strings (`"12"`), or not at all when stats were not loaded. */
export const toCount = (value: number | string | null | undefined): number => {
	const parsed = typeof value === 'number' ? value : Number(value ?? 0);

	return Number.isFinite(parsed) && parsed > 0 ? Math.trunc(parsed) : 0;
};

const countFormat = new Intl.NumberFormat('en-US');

export const formatCount = (value: number): string => countFormat.format(value);

const dateFormat = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

/** "2 Oct 2026", in the viewer's time zone. An unparseable value is returned as it came. */
export const formatDashboardDate = (iso: string): string => {
	const date = new Date(iso);

	return Number.isNaN(date.getTime()) ? iso : dateFormat.format(date);
};

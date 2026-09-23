import React from 'react';
import classNames from 'classnames';
import {
	ProcessingStatus,
	type ProcessingStatus as ProcessingStatusType,
} from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import ProcessingStatusBadge from '@/components/features/ProcessingStatusAlert/ProcessingStatusBadge';
import Spinner from '@/components/shared/Spinner';
import {
	Popover,
	PopoverContent,
	PopoverDescription,
	PopoverHeader,
	PopoverTitle,
	PopoverTrigger,
} from '@/components/ui/popover';
import { STATUS_VARIANT_CLASSES, type StatusVariant } from '@/components/ui/status-colors';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import styles from './ProcessingStatusAlert.module.css';

/**
 * Copy per status. `live` is true when the socket is connected and events arrive as they
 * happen; otherwise the query is polling (#251) and the page should promise only that.
 */
export const STATUS_CONFIG: Record<ProcessingStatusType, { message: (live: boolean) => string }> = {
	pending: {
		message: (live) =>
			live
				? 'Kanji and vocabulary for this article are queued. This page will update automatically.'
				: 'Kanji and vocabulary for this article are queued. Checking for updates.',
	},
	processing: {
		message: (live) =>
			live
				? 'Extracting kanji and vocabulary for this article. This page will update automatically.'
				: 'Extracting kanji and vocabulary for this article. Checking for updates.',
	},
	completed: {
		message: () => 'Kanji and vocabulary for this article are ready.',
	},
	failed: {
		message: () => 'Kanji and vocabulary extraction failed for this article. Please try again later.',
	},
	superseded: {
		message: () => 'Content changed while processing; the newer version has been processed instead.',
	},
};

interface ProcessingStatusAlertProps {
	processing_status?: ProcessingStatusResource | null;
	className?: string;
}

const formatDurationCompact = (ms: number): string => {
	const totalSeconds = Math.max(0, Math.floor(ms / 1000));
	const hours = Math.floor(totalSeconds / 3600);
	const minutes = Math.floor((totalSeconds % 3600) / 60);
	const seconds = totalSeconds % 60;

	const parts: string[] = [];
	if (hours > 0) parts.push(`${hours}h`);
	if (minutes > 0) parts.push(`${minutes}m`);
	parts.push(`${seconds}s`);

	return parts.join(' ');
};

// TODO: Should perhaps allow to close permanently, after each processing,
// probably saving the last state on browser storage.
// Or change UI presentation for smarter UX
const ProcessingStatusAlert: React.FC<ProcessingStatusAlertProps> = ({ processing_status, className }) => {
	const { isConnected } = useWebSocket();
	const status = processing_status?.status;

	// Superseded is terminal and carries no result of its own (ADR 0001): nothing to show.
	if (!status || status === ProcessingStatus.superseded) return null;

	const message = STATUS_CONFIG[status].message(isConnected);

	const createdAt = processing_status?.created_at ? new Date(processing_status.created_at) : null;
	const updatedAt = processing_status?.updated_at ? new Date(processing_status.updated_at) : null;

	const createdAtMs = createdAt instanceof Date && !Number.isNaN(createdAt.getTime()) ? createdAt.getTime() : null;
	const updatedAtMs = updatedAt instanceof Date && !Number.isNaN(updatedAt.getTime()) ? updatedAt.getTime() : null;

	const createdAtText = createdAtMs !== null ? new Date(createdAtMs).toLocaleString() : null;
	const updatedAtText = updatedAtMs !== null ? new Date(updatedAtMs).toLocaleString() : null;

	const hasValidTiming = createdAtMs !== null && updatedAtMs !== null;

	const isTerminal = status === ProcessingStatus.completed || status === ProcessingStatus.failed;

	// A first attempt is the normal case and says nothing worth the space; a retry does (#261).
	const attempt = processing_status?.attempt ?? 0;
	const maxAttempts = processing_status?.max_attempts ?? 0;
	const attemptText = attempt > 1 ? `Attempt ${attempt}${maxAttempts > 0 ? ` of ${maxAttempts}` : ''}` : null;

	let durationText: string | null = null;
	if (isTerminal && createdAtMs !== null && updatedAtMs !== null) {
		durationText = formatDurationCompact(updatedAtMs - createdAtMs);
	}

	// TODO: not sure about this class mapping if it is the clean way.
	const statusVariant: StatusVariant =
		status === ProcessingStatus.completed
			? 'success'
			: status === ProcessingStatus.failed
				? 'destructive'
				: 'pending';

	const details: Array<{ label: string; value: string | null }> = [
		{ label: 'Created', value: createdAtText },
		{ label: 'Updated', value: updatedAtText },
		{ label: 'Duration', value: durationText },
		...(attemptText ? [{ label: 'Retry', value: attemptText }] : []),
	];

	return (
		<div className={classNames(styles.alert, STATUS_VARIANT_CLASSES[statusVariant], className)}>
			<div className={styles.content}>
				<p className={styles.message}>{message}</p>
				<div className={styles.status}>
					{(status === ProcessingStatus.pending || status === ProcessingStatus.processing) && (
						<span className={styles.spinner} aria-hidden="true">
							<Spinner size="sm" />
						</span>
					)}
					<Popover>
						<PopoverTrigger asChild>
							<button type="button" className={styles.popoverTrigger}>
								<ProcessingStatusBadge status={status} />
							</button>
						</PopoverTrigger>
						<PopoverContent align="end">
							<PopoverHeader>
								<PopoverTitle>Processing details</PopoverTitle>
								<PopoverDescription>Times are shown in your local timezone.</PopoverDescription>
							</PopoverHeader>
							<dl className={styles.details}>
								{details.map(({ label, value }) => (
									<div key={label} className={styles.detailRow}>
										<dt className={styles.detailLabel}>{label}</dt>
										<dd className={styles.detailValue}>{value ?? '—'}</dd>
									</div>
								))}
							</dl>
							{!hasValidTiming && <p className={styles.detailNote}>Timing data unavailable.</p>}
						</PopoverContent>
					</Popover>
				</div>
			</div>
		</div>
	);
};

export default ProcessingStatusAlert;

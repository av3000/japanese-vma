import React, { useEffect, useRef, useState } from 'react';
import classNames from 'classnames';
import {
	ProcessingStatus,
	type ProcessingStatus as ProcessingStatusType,
} from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { Alert } from '@/components/shared/Alert';
import Spinner from '@/components/shared/Spinner';
import { processingStatusPill, StatusPill } from '@/components/shared/StatusPill';
import { formatDurationCompact } from '@/helpers/date';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import styles from './ProcessingStatusAlert.module.css';

type VisibleStatus =
	| typeof ProcessingStatus.pending
	| typeof ProcessingStatus.processing
	| typeof ProcessingStatus.failed;

/**
 * Copy per status that shows an alert. `live` is true when the socket is connected and events
 * arrive as they happen; otherwise the query is polling (#251) and the page promises only that.
 * A failure tells the owner how to run the analysis again and everyone else only that the lists
 * are not there; the server's error text never reaches the page.
 */
export const STATUS_CONFIG: Record<VisibleStatus, { message: (live: boolean, isOwner: boolean) => string }> = {
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
	failed: {
		message: (_live, isOwner) =>
			isOwner
				? "Kanji and vocabulary couldn't be extracted. Saving a change to the Japanese title or text runs it again."
				: "Kanji and vocabulary aren't available for this article yet.",
	},
};

export const READY_ANNOUNCEMENT = 'Kanji and vocabulary are ready.';

const isVisibleStatus = (status: ProcessingStatusType | undefined): status is VisibleStatus =>
	status === ProcessingStatus.pending || status === ProcessingStatus.processing || status === ProcessingStatus.failed;

interface ProcessingStatusAlertProps {
	processing_status?: ProcessingStatusResource | null;
	/** The article's author, who can run the analysis again by editing the Japanese text. */
	isOwner?: boolean;
	className?: string;
}

const toTime = (value: string | null | undefined): number | null => {
	if (!value) return null;
	const time = new Date(value).getTime();

	return Number.isNaN(time) ? null : time;
};

/**
 * Article processing while it is still worth knowing about: queued, running or failed. A completed
 * (or superseded) analysis is silent, because the kanji and words on the page already say so. When
 * it completes while the page is open, a polite status announces it once.
 */
const ProcessingStatusAlert: React.FC<ProcessingStatusAlertProps> = ({
	processing_status,
	isOwner = false,
	className,
}) => {
	const { isConnected } = useWebSocket();
	const status = processing_status?.status;
	const previousStatus = useRef(status);
	const [announcement, setAnnouncement] = useState('');

	useEffect(() => {
		const wasRunning =
			previousStatus.current === ProcessingStatus.pending ||
			previousStatus.current === ProcessingStatus.processing;

		if (wasRunning && status === ProcessingStatus.completed) {
			setAnnouncement(READY_ANNOUNCEMENT);
		} else if (status !== ProcessingStatus.completed) {
			setAnnouncement('');
		}

		previousStatus.current = status;
	}, [status]);

	// The live region is always present, so the completion announcement is read when it fills.
	const liveRegion = (
		<p className={styles.visuallyHidden} role="status">
			{announcement}
		</p>
	);

	if (!isVisibleStatus(status)) return liveRegion;

	const createdAtMs = toTime(processing_status?.created_at);
	const updatedAtMs = toTime(processing_status?.updated_at);
	const isFailed = status === ProcessingStatus.failed;

	// A first attempt is the normal case and says nothing worth the space; a retry does (#261).
	const attempt = processing_status?.attempt ?? 0;
	const maxAttempts = processing_status?.max_attempts ?? 0;
	const attemptText = attempt > 1 ? `Attempt ${attempt}${maxAttempts > 0 ? ` of ${maxAttempts}` : ''}` : null;

	const details: Array<{ label: string; value: string | null }> = [
		{ label: 'Started', value: createdAtMs !== null ? new Date(createdAtMs).toLocaleString() : null },
		{ label: 'Updated', value: updatedAtMs !== null ? new Date(updatedAtMs).toLocaleString() : null },
		...(isFailed && createdAtMs !== null && updatedAtMs !== null
			? [{ label: 'Duration', value: formatDurationCompact(updatedAtMs - createdAtMs) }]
			: []),
		...(attemptText ? [{ label: 'Retry', value: attemptText }] : []),
	];

	// The pill spins for processing on its own; a queued article gets the alert's spinner.
	return (
		<>
			<Alert
				tone={isFailed ? 'danger' : 'info'}
				// Polite even when failed: on page load the failure is context, not an interruption.
				role="status"
				className={classNames(styles.alert, className)}
				actions={
					<span className={styles.status}>
						{status === ProcessingStatus.pending && (
							<span className={styles.spinner} aria-hidden="true">
								<Spinner size="sm" />
							</span>
						)}
						<StatusPill {...processingStatusPill(status)} />
					</span>
				}
			>
				<p className={styles.message}>{STATUS_CONFIG[status].message(isConnected, isOwner)}</p>
				<details className={styles.details}>
					<summary className={styles.summary}>Processing details</summary>
					<dl className={styles.detailList}>
						{details.map(({ label, value }) => (
							<div key={label} className={styles.detailRow}>
								<dt className={styles.detailLabel}>{label}</dt>
								<dd className={styles.detailValue}>{value ?? '—'}</dd>
							</div>
						))}
					</dl>
				</details>
			</Alert>
			{liveRegion}
		</>
	);
};

export default ProcessingStatusAlert;

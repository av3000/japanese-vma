import React from 'react';
import classNames from 'classnames';
import { LastOperationEvent, LastOperationStatus } from '@/api/last-operations/last-operations';
import ProcessingStatusBadge from '@/components/features/ProcessingStatusAlert/ProcessingStatusBadge';
import { STATUS_VARIANT_BASE_CLASSES, type StatusVariant } from '@/components/ui/status-colors';
import {
	Popover,
	PopoverContent,
	PopoverDescription,
	PopoverHeader,
	PopoverTitle,
	PopoverTrigger,
} from '@/components/ui/popover';
import styles from './ProcessingStatusAlert.module.scss';

export const STATUS_CONFIG: Record<LastOperationStatus, { message: string }> = {
	pending: {
		message: 'Instance queued for processing. This page will update automatically.',
	},
	processing: {
		message: 'Instance background processing. Please wait, this page will update automatically.',
	},
	completed: {
		message: 'Instance processing complete.',
	},
	failed: {
		message: 'Instance processing failed. Please try again later.',
	},
};

interface ProcessingStatusAlertProps {
	processing_status?: LastOperationEvent | null;
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

// TODO: Should perhaps allow to close permanently, after each processing, probably saving the last state on browser storage
const ProcessingStatusAlert: React.FC<ProcessingStatusAlertProps> = ({ processing_status, className }) => {
	const status = processing_status?.status;

	if (!status) return null;

	const config = STATUS_CONFIG[status];

	const createdAt = processing_status?.created_at ? new Date(processing_status.created_at) : null;
	const updatedAt = processing_status?.updated_at ? new Date(processing_status.updated_at) : null;

	const createdAtMs = createdAt instanceof Date && !Number.isNaN(createdAt.getTime()) ? createdAt.getTime() : null;
	const updatedAtMs = updatedAt instanceof Date && !Number.isNaN(updatedAt.getTime()) ? updatedAt.getTime() : null;

	const createdAtText = createdAtMs !== null ? new Date(createdAtMs).toLocaleString() : null;
	const updatedAtText = updatedAtMs !== null ? new Date(updatedAtMs).toLocaleString() : null;

	const hasValidTiming = createdAtMs !== null && updatedAtMs !== null;

	const isTerminal = status === LastOperationStatus.Completed || status === LastOperationStatus.Failed;

	let durationText: string | null = null;
	if (isTerminal && createdAtMs !== null && updatedAtMs !== null) {
		durationText = formatDurationCompact(updatedAtMs - createdAtMs);
	}

	const statusVariant: StatusVariant =
		status === LastOperationStatus.Completed
			? 'success'
			: status === LastOperationStatus.Failed
				? 'destructive'
				: 'pending';

	return (
		<div
			className={classNames(
				'mt-3 rounded-md border border-transparent px-3 py-2',
				STATUS_VARIANT_BASE_CLASSES[statusVariant],
				styles.alert,
				className,
			)}
		>
			<div className={styles.content}>
				<div className="small">{config.message}</div>
				<div className={styles.status}>
					{(status === LastOperationStatus.Pending || status === LastOperationStatus.Processing) && (
						<span className="spinner-border spinner-border-sm mr-3" />
					)}
					<Popover>
						<PopoverTrigger asChild>
							<button type="button" className={styles.popoverTrigger}>
								<ProcessingStatusBadge status={status} />
							</button>
						</PopoverTrigger>
						<PopoverContent align="end" className="w-80">
							<PopoverHeader>
								<PopoverTitle>Processing details</PopoverTitle>
								<PopoverDescription>Times are shown in your local timezone.</PopoverDescription>
							</PopoverHeader>
							<div className="mt-3 d-grid gap-2">
								<div className="d-flex justify-content-between gap-3">
									<span className="text-muted small">Created</span>
									<span className="small">{createdAtText ?? '—'}</span>
								</div>
								<div className="d-flex justify-content-between gap-3">
									<span className="text-muted small">Updated</span>
									<span className="small">{updatedAtText ?? '—'}</span>
								</div>
								<div className="d-flex justify-content-between gap-3">
									<span className="text-muted small">Duration</span>
									<span className="small">{durationText ?? '—'}</span>
								</div>

								{!hasValidTiming && (
									<div className="small text-muted mt-2">Timing data unavailable.</div>
								)}
							</div>
						</PopoverContent>
					</Popover>
				</div>
			</div>
		</div>
	);
};

export default ProcessingStatusAlert;

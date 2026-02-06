import React from 'react';
import classNames from 'classnames';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import ProcessingStatusBadge from '@/components/features/ProcessingStatusAlert/ProcessingStatusBadge';
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
	status?: LastOperationStatus | null;
	className?: string;
}

// TODO: Should allow to close permanently, after each processing, probably saving the last state on browser storage
const ProcessingStatusAlert: React.FC<ProcessingStatusAlertProps> = ({ status, className }) => {
	if (!status) return null;

	const config = STATUS_CONFIG[status];

	return (
		<div className={classNames('alert mt-3', styles.alert, styles[status], className)}>
			<div className={styles.content}>
				<div className="small">{config.message}</div>
				<div className={styles.status}>
					{(status === LastOperationStatus.Pending || status === LastOperationStatus.Processing) && (
						<span className="spinner-border spinner-border-sm mr-3" />
					)}
					<ProcessingStatusBadge status={status} />
				</div>
			</div>
		</div>
	);
};

export default ProcessingStatusAlert;

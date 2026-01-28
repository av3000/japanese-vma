import React from 'react';
import classNames from 'classnames';
import { LastOperationStatus } from '@/api/articles/articles';
import { ButtonVariant } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import styles from './ProcessingStatusAlert.module.scss';

export const STATUS_CONFIG: Record<LastOperationStatus, { label: string; message: string; variant: ButtonVariant }> = {
	pending: {
		label: 'Pending',
		message: 'Article queued for processing. This page will update automatically.',
		variant: 'secondary-outline',
	},
	processing: {
		label: 'Processing',
		message: 'Article background processing. Please wait, this page will update automatically.',
		variant: 'outline',
	},
	completed: {
		label: 'Completed',
		message: 'Article processing complete.',
		variant: 'success',
	},
	failed: {
		label: 'Failed',
		message: 'Article processing failed. Please try again later.',
		variant: 'danger',
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
					<Chip readonly variant={config.variant}>
						{config.label}
					</Chip>
				</div>
			</div>
		</div>
	);
};

export default ProcessingStatusAlert;

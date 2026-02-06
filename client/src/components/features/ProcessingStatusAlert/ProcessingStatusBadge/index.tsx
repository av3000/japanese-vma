import classNames from 'classnames';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import { Badge } from '@/components/ui/badge';
import { Icon } from '../../../shared/Icon';

interface ProcessingStatusBadgeProps {
	className?: string;
	status: LastOperationStatus;
	isOnlyIcon?: boolean;
	showPrefix?: boolean;
}

const STATUS_CONFIG: Record<
	LastOperationStatus,
	{
		variant: 'success' | 'pending' | 'destructive';
		icon: 'checkSolid' | 'minusSolid' | 'removeSolid';
		label: string;
	}
> = {
	pending: { variant: 'pending', icon: 'minusSolid', label: 'Pending' },
	processing: { variant: 'pending', icon: 'minusSolid', label: 'Processing' },
	completed: { variant: 'success', icon: 'checkSolid', label: 'Completed' },
	failed: { variant: 'destructive', icon: 'removeSolid', label: 'Failed' },
};

const ProcessingStatusBadge: React.FC<ProcessingStatusBadgeProps> = ({
	className,
	status,
	isOnlyIcon = false,
	showPrefix = false,
}: ProcessingStatusBadgeProps) => {
	const config = STATUS_CONFIG[status];

	return (
		<Badge
			isOnlyIcon={isOnlyIcon}
			variant={config.variant}
			className={classNames(className)}
			aria-label={isOnlyIcon ? config.label : undefined}
		>
			{isOnlyIcon ? (
				<Icon size="sm" name={config.icon} />
			) : (
				<>
					{showPrefix ? 'Status:' : null}
					<Icon size="sm" name={config.icon} />
					{config.label}
				</>
			)}
		</Badge>
	);
};

export default ProcessingStatusBadge;

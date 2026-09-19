import classNames from 'classnames';
import {
	ProcessingStatus,
	type ProcessingStatus as ProcessingStatusType,
} from '@/api/generated/model/processingStatus';
import Spinner from '@/components/shared/Spinner';
import { Badge } from '@/components/ui/badge';
import { Icon } from '../../../shared/Icon';

interface ProcessingStatusBadgeProps {
	className?: string;
	status: ProcessingStatusType;
	isOnlyIcon?: boolean;
	showPrefix?: boolean;
}

const STATUS_CONFIG: Record<
	ProcessingStatusType,
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
	// Terminal and normally hidden by callers; kept so every status has a rendering.
	superseded: { variant: 'pending', icon: 'minusSolid', label: 'Superseded' },
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
				<>
					{status === ProcessingStatus.processing ? (
						<Spinner size="sm" />
					) : (
						<Icon size="sm" name={config.icon} />
					)}
				</>
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

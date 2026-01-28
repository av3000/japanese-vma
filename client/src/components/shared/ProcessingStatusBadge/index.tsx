import { LastOperationStatus } from '@/api/articles/articles';
import { Badge } from '@/components/ui/badge';
import { Icon } from '../Icon';

interface ProcessingStatusBadgeProps {
	status: LastOperationStatus;
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

const ProcessingStatusBadge: React.FC<ProcessingStatusBadgeProps> = ({ status }: ProcessingStatusBadgeProps) => {
	const config = STATUS_CONFIG[status];
	return (
		<div className="mb-2">
			<Badge variant={config.variant}>
				<Icon size="sm" name={config.icon} />
				{config.label}
			</Badge>
		</div>
	);
};

export default ProcessingStatusBadge;

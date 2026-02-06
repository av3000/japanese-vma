import React, { useMemo } from 'react';
import { OverlayTrigger, Popover } from 'react-bootstrap';
import { Badge } from '@/components/ui/badge';
import { useWebSocket } from '@/providers/contexts/socket-provider';

const SocketStatusIndicator: React.FC = () => {
	const { connectionStatus, lastError, connectionInfo, hasAttemptedConnection } = useWebSocket();

	const { variant, label } = useMemo(() => {
		switch (connectionStatus) {
			case 'connected':
				return { variant: 'success' as const, label: 'connected' };
			case 'connecting':
			case 'reconnecting':
				return { variant: 'pending' as const, label: connectionStatus };
			case 'failed':
			case 'disconnected':
			default:
				return { variant: 'destructive' as const, label: connectionStatus };
		}
	}, [connectionStatus]);

	const popover = (
		<Popover id="socket-status-popover">
			<Popover.Header as="h3">WebSocket</Popover.Header>
			<Popover.Body>
				<div className="mb-1">
					<strong>Status:</strong> {hasAttemptedConnection ? label : 'initializing'}
				</div>
				<div className="small mb-1">
					<strong>Target:</strong> {connectionInfo.scheme}://{connectionInfo.host}:{connectionInfo.port}
				</div>
				{lastError && (
					<div className="small">
						<strong>Last error:</strong> {lastError}
					</div>
				)}
			</Popover.Body>
		</Popover>
	);

	return (
		<OverlayTrigger
			trigger={['hover', 'focus', 'click']}
			placement="bottom"
			overlay={popover}
			rootClose
			container={typeof document !== 'undefined' ? document.body : undefined}
		>
			<button
				type="button"
				aria-label={`WebSocket ${hasAttemptedConnection ? label : 'initializing'}`}
				className="relative inline-flex items-center justify-center rounded-full p-1"
			>
				<Badge aria-hidden="true" className="h-2.5 w-2.5 rounded-full p-0" variant={variant} />
			</button>
		</OverlayTrigger>
	);
};

export default SocketStatusIndicator;

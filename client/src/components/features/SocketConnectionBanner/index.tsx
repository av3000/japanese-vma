import React from 'react';
import { useAuth } from '@/hooks/useAuth';
import { useWebSocket } from '@/providers/contexts/socket-provider';

const SocketConnectionBanner: React.FC = () => {
	const { user } = useAuth();
	const { connectionStatus, lastError, connectionInfo } = useWebSocket();

	if (!user?.isAdmin) {
		return null;
	}

	const isWarning = connectionStatus === 'connecting' || connectionStatus === 'reconnecting';
	const variantClass = isWarning ? 'alert-warning' : 'alert-danger';

	return (
		<div className={`alert ${variantClass} mb-0`} role="alert">
			<div className="container py-1">
				<div>
					<strong>WebSocket:</strong> {connectionStatus}
				</div>
				<div className="small">
					Target: {connectionInfo.scheme}://{connectionInfo.host}:{connectionInfo.port} (app key{' '}
					{connectionInfo.appKey || 'n/a'})
				</div>
				{lastError && <div className="small">Last error: {lastError}</div>}
				<div className="small">Real-time updates are unavailable; refresh after operations complete.</div>
			</div>
		</div>
	);
};

export default SocketConnectionBanner;

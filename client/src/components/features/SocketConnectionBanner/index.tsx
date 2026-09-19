import React from 'react';
import { useAuth } from '@/hooks/useAuth';
import { useWebSocket } from '@/providers/contexts/socket-provider';

/**
 * Admin-only diagnostic strip for the real-time transport. Informational: when the socket is
 * down the processing queries poll instead (#251), so nothing asks the user to refresh, and a
 * healthy connection shows nothing at all.
 */
const SocketConnectionBanner: React.FC = () => {
	const { user } = useAuth();
	const { connectionStatus, lastError, connectionInfo, isConfigured } = useWebSocket();

	if (!user?.isAdmin || !isConfigured || connectionStatus === 'connected') {
		return null;
	}

	const isTransient = connectionStatus === 'connecting' || connectionStatus === 'reconnecting';
	const variantClass = isTransient ? 'alert-warning' : 'alert-danger';

	return (
		<div className={`alert ${variantClass} mb-0`} role="status">
			<div className="container py-1">
				<div>
					<strong>WebSocket:</strong> {connectionStatus}
				</div>
				<div className="small">
					Target: {connectionInfo.scheme}://{connectionInfo.host}:{connectionInfo.port} (app key{' '}
					{connectionInfo.appKey || 'n/a'})
				</div>
				{lastError && <div className="small">Last error: {lastError}</div>}
				<div className="small">Live updates are unavailable; processing status is polled instead.</div>
			</div>
		</div>
	);
};

export default SocketConnectionBanner;

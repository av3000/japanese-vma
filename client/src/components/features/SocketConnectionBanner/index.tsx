import React from 'react';
import { Alert } from '@/components/shared/Alert';
import { Container } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import styles from './SocketConnectionBanner.module.css';

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

	const isWarning = connectionStatus === 'connecting' || connectionStatus === 'reconnecting';

	return (
		<Alert tone={isWarning ? 'warning' : 'danger'} className={styles.banner}>
			<Container>
				<p className={styles.line}>
					<strong>WebSocket:</strong> {connectionStatus}
				</p>
				<p className={styles.detail}>
					Target: {connectionInfo.scheme}://{connectionInfo.host}:{connectionInfo.port} (app key{' '}
					{connectionInfo.appKey || 'n/a'})
				</p>
				{lastError && <p className={styles.detail}>Last error: {lastError}</p>}
				<p className={styles.detail}>Live updates are unavailable; processing status is polled instead.</p>
			</Container>
		</Alert>
	);
};

export default SocketConnectionBanner;

import React from 'react';
import { Alert } from '@/components/shared/Alert';
import { Container } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import styles from './SocketConnectionBanner.module.css';

const SocketConnectionBanner: React.FC = () => {
	const { user } = useAuth();
	const { connectionStatus, lastError, connectionInfo, isConfigured } = useWebSocket();

	if (!user?.isAdmin || !isConfigured) {
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
				<p className={styles.detail}>Real-time updates are unavailable; refresh after operations complete.</p>
			</Container>
		</Alert>
	);
};

export default SocketConnectionBanner;

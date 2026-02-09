import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import type Echo from 'laravel-echo';
import { useAuth } from '@/hooks/useAuth';
import { configureEcho, echo } from '@/lib/echo';
import type { ConnectionStatus } from '@/lib/echo/types';

interface SocketContextType {
	echo: Echo<'reverb'> | null;
	isConnected: boolean;
	connectionStatus: ConnectionStatus;
	lastError: string | null;
	connectionInfo: { host: string; port: number; scheme: 'ws' | 'wss'; appKey: string };
	hasAttemptedConnection: boolean;
}

const SocketContext = createContext<SocketContextType>({
	echo: null,
	isConnected: false,
	connectionStatus: 'disconnected',
	lastError: null,
	connectionInfo: { host: 'localhost', port: 8081, scheme: 'ws', appKey: '' },
	hasAttemptedConnection: false,
});

export const useWebSocket = () => useContext(SocketContext);

const mapPusherState = (state: string | undefined): ConnectionStatus => {
	switch (state) {
		case 'initialized':
		case 'connecting':
			return 'connecting';
		case 'connected':
			return 'connected';
		case 'unavailable':
			return 'reconnecting';
		case 'failed':
			return 'failed';
		case 'disconnected':
			return 'disconnected';
		default:
			return 'disconnected';
	}
};

const safeStringify = (value: unknown): string => {
	try {
		return JSON.stringify(value);
	} catch {
		return String(value);
	}
};

export const WebSocketProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
	const [echoClient, setEchoClient] = useState<Echo<'reverb'> | null>(null);
	const [connectionStatus, setConnectionStatus] = useState<ConnectionStatus>('disconnected');
	const [lastError, setLastError] = useState<string | null>(null);
	const [hasAttemptedConnection, setHasAttemptedConnection] = useState(false);
	const { token } = useAuth();

	const connectionInfo = useMemo(() => {
		const envHost = import.meta.env.VITE_REVERB_HOST;
		const host = !envHost || envHost === '0.0.0.0' ? window.location.hostname : envHost;
		const parsedPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT, 10) : 8081;
		const port = Number.isFinite(parsedPort) ? parsedPort : 8081;
		const appKey = import.meta.env.VITE_REVERB_APP_KEY ?? '';
		const scheme: 'ws' | 'wss' = import.meta.env.VITE_REVERB_SCHEME === 'https' ? 'wss' : 'ws';

		return { host, port, scheme, appKey };
	}, []);

	useEffect(() => {
		const wsHost = connectionInfo.host;
		const wsPort = connectionInfo.port;

		const authHeaders: Record<string, string> = {
			Accept: 'application/json',
		};

		if (token) {
			authHeaders.Authorization = `Bearer ${token}`;
		}

		configureEcho({
			broadcaster: 'reverb',
			key: import.meta.env.VITE_REVERB_APP_KEY,
			wsHost,
			wsPort,
			wssPort: wsPort,
			forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
			enabledTransports: ['ws', 'wss'],
			disableStats: true,
			cluster: 'mt1',
			authEndpoint: `${import.meta.env.VITE_API_URL}/api/broadcasting/auth`,
			auth: {
				headers: authHeaders,
			},
		});

		const echoInstance = echo<'reverb'>();

		setEchoClient(echoInstance);
		setHasAttemptedConnection(true);

		const connector = echoInstance.connector as unknown;
		const pusher = connector && typeof connector === 'object' && 'pusher' in connector ? (connector as any).pusher : null;
		const connection = pusher && typeof pusher === 'object' && 'connection' in pusher ? (pusher as any).connection : null;
		const hasBind = connection && typeof connection.bind === 'function' && typeof connection.unbind === 'function';

		const stateChangeHandler = (payload: any) => {
			const previous = typeof payload?.previous === 'string' ? payload.previous : undefined;
			const current = typeof payload?.current === 'string' ? payload.current : typeof payload === 'string' ? payload : undefined;
			const mapped = mapPusherState(current);

			setConnectionStatus(mapped);

			if (import.meta.env.DEV) {
				console.info(
					`[reverb] state_change previous=${previous ?? 'n/a'} current=${current ?? 'n/a'} mapped=${mapped} host=${connectionInfo.host}:${connectionInfo.port}`,
				);
			}
		};

		const errorHandler = (payload: any) => {
			const message =
				(typeof payload?.error?.data?.message === 'string' && payload.error.data.message) ||
				(typeof payload?.error?.message === 'string' && payload.error.message) ||
				(typeof payload?.message === 'string' && payload.message) ||
				safeStringify(payload);

			setLastError(message);

			if (import.meta.env.DEV) {
				console.warn('[reverb] error', payload);
			}
		};

		if (hasBind) {
			setConnectionStatus(mapPusherState(connection.state));
			connection.bind('state_change', stateChangeHandler);
			connection.bind('error', errorHandler);
		} else {
			setConnectionStatus('disconnected');
			setLastError('Reverb connector is missing a Pusher connection object.');
		}

		return () => {
			if (hasBind) {
				connection.unbind('state_change', stateChangeHandler);
				connection.unbind('error', errorHandler);
			}

			echoInstance.disconnect();
			setEchoClient(null);
			setConnectionStatus('disconnected');
		};
	}, [token, connectionInfo.host, connectionInfo.port]);

	const isConnected = connectionStatus === 'connected';

	return (
		<SocketContext.Provider
			value={{ echo: echoClient, isConnected, connectionStatus, lastError, connectionInfo, hasAttemptedConnection }}
		>
			{children}
		</SocketContext.Provider>
	);
};

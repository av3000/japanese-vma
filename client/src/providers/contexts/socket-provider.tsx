import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import type Echo from 'laravel-echo';
import { useAuth } from '@/hooks/useAuth';
import type { ConnectionStatus } from '@/lib/echo/types';

export interface SocketContextType {
	/** The live Echo instance, or null until the provider has configured one. */
	echo: Echo<'reverb'> | null;
	/**
	 * Incremented every time a new instance is created (first configure, token change).
	 * Subscribers key their subscriptions on it so a fresh instance always gets fresh
	 * subscriptions and a stale one is never reused (#253).
	 */
	generation: number;
	isConnected: boolean;
	isConfigured: boolean;
	connectionStatus: ConnectionStatus;
	lastError: string | null;
	connectionInfo: { host: string; port: number; scheme: 'ws' | 'wss'; appKey: string };
	hasAttemptedConnection: boolean;
}

export const shouldStartWebSocket = ({
	isAuthenticated,
	isConfigured,
	token,
}: {
	isAuthenticated: boolean;
	isConfigured: boolean;
	token: string | null;
}) => Boolean(isConfigured && isAuthenticated && token);

export const DEFAULT_SOCKET_CONTEXT: SocketContextType = {
	echo: null,
	generation: 0,
	isConnected: false,
	isConfigured: false,
	connectionStatus: 'disconnected',
	lastError: null,
	connectionInfo: { host: 'localhost', port: 8081, scheme: 'ws', appKey: '' },
	hasAttemptedConnection: false,
};

export const SocketContext = createContext<SocketContextType>(DEFAULT_SOCKET_CONTEXT);

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

/**
 * Reads the client-side Reverb target. `VITE_REVERB_HOST` is the browser-facing host and is
 * documented in `client/.env.example`; it is no longer the backend's bind address (#254).
 */
const readConnectionInfo = (): SocketContextType['connectionInfo'] => {
	const host = import.meta.env.VITE_REVERB_HOST || 'localhost';
	const parsedPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT, 10) : 8081;
	const port = Number.isFinite(parsedPort) ? parsedPort : 8081;
	const appKey = import.meta.env.VITE_REVERB_APP_KEY ?? '';
	const scheme: 'ws' | 'wss' = import.meta.env.VITE_REVERB_SCHEME === 'https' ? 'wss' : 'ws';

	return { host, port, scheme, appKey };
};

export const WebSocketProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
	const [echoClient, setEchoClient] = useState<Echo<'reverb'> | null>(null);
	const [generation, setGeneration] = useState(0);
	const [connectionStatus, setConnectionStatus] = useState<ConnectionStatus>('disconnected');
	const [lastError, setLastError] = useState<string | null>(null);
	const [hasAttemptedConnection, setHasAttemptedConnection] = useState(false);
	const { isAuthenticated, token } = useAuth();

	const connectionInfo = useMemo(readConnectionInfo, []);

	const isConfigured = connectionInfo.appKey.trim().length > 0;

	useEffect(() => {
		if (!shouldStartWebSocket({ isConfigured, isAuthenticated, token })) {
			setEchoClient(null);
			setHasAttemptedConnection(false);
			setConnectionStatus('disconnected');
			setLastError(null);
			return;
		}

		let isActive = true;
		let echoInstance: Echo<'reverb'> | null = null;
		let cleanupConnection: (() => void) | null = null;

		const connect = async () => {
			// Dynamic import keeps laravel-echo and pusher-js in their own chunk.
			const { createEcho } = await import('@/lib/echo/config/index');

			if (!isActive) {
				return;
			}

			echoInstance = createEcho<'reverb'>({
				broadcaster: 'reverb',
				key: connectionInfo.appKey,
				wsHost: connectionInfo.host,
				wsPort: connectionInfo.port,
				wssPort: connectionInfo.port,
				forceTLS: connectionInfo.scheme === 'wss',
				enabledTransports: ['ws', 'wss'],
				disableStats: true,
				authEndpoint: `${import.meta.env.VITE_API_URL}/api/broadcasting/auth`,
				auth: {
					headers: {
						Accept: 'application/json',
						Authorization: `Bearer ${token}`,
					},
				},
			});

			setEchoClient(echoInstance);
			setGeneration((current) => current + 1);
			setHasAttemptedConnection(true);

			const connector = echoInstance.connector as unknown;
			const pusher =
				connector && typeof connector === 'object' && 'pusher' in connector ? (connector as any).pusher : null;
			const connection =
				pusher && typeof pusher === 'object' && 'connection' in pusher ? (pusher as any).connection : null;
			const hasBind =
				connection && typeof connection.bind === 'function' && typeof connection.unbind === 'function';

			const stateChangeHandler = (payload: any) => {
				const previous = typeof payload?.previous === 'string' ? payload.previous : undefined;
				const current =
					typeof payload?.current === 'string'
						? payload.current
						: typeof payload === 'string'
							? payload
							: undefined;
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
				cleanupConnection = () => {
					connection.unbind('state_change', stateChangeHandler);
					connection.unbind('error', errorHandler);
				};
			} else {
				setConnectionStatus('disconnected');
				setLastError('Reverb connector is missing a Pusher connection object.');
			}
		};

		void connect();

		return () => {
			isActive = false;
			cleanupConnection?.();
			echoInstance?.disconnect();
			setEchoClient(null);
			setConnectionStatus('disconnected');
		};
	}, [isAuthenticated, token, connectionInfo, isConfigured]);

	const isConnected = connectionStatus === 'connected';

	const value = useMemo<SocketContextType>(
		() => ({
			echo: echoClient,
			generation,
			isConnected,
			isConfigured,
			connectionStatus,
			lastError,
			connectionInfo,
			hasAttemptedConnection,
		}),
		[
			echoClient,
			generation,
			isConnected,
			isConfigured,
			connectionStatus,
			lastError,
			connectionInfo,
			hasAttemptedConnection,
		],
	);

	return <SocketContext.Provider value={value}>{children}</SocketContext.Provider>;
};

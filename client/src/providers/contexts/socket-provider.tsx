import React, { createContext, useContext, useEffect, useState } from 'react';
import type Echo from 'laravel-echo';
import { useAuth } from '@/hooks/useAuth';
import { configureEcho, echo } from '@/lib/echo';

interface SocketContextType {
	echo: Echo<'reverb'> | null;
	isConnected: boolean;
}

const SocketContext = createContext<SocketContextType>({
	echo: null,
	isConnected: false,
});

export const useWebSocket = () => useContext(SocketContext);

export const WebSocketProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
	const [echoClient, setEchoClient] = useState<Echo<'reverb'> | null>(null);
	const [isConnected, setIsConnected] = useState(false);
	const { token } = useAuth();

	useEffect(() => {
		const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
		const wsPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT, 10) : 8081;

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
		setIsConnected(true);

		return () => {
			echoInstance.disconnect();
			setEchoClient(null);
			setIsConnected(false);
		};
	}, [token]);

	return <SocketContext.Provider value={{ echo: echoClient, isConnected }}>{children}</SocketContext.Provider>;
};

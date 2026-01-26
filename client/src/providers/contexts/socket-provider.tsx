import React, { createContext, useContext, useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useAuth } from '@/hooks/useAuth';

declare global {
	interface Window {
		Pusher: any;
	}
}

window.Pusher = Pusher;

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
	const [echo, setEcho] = useState<Echo<'reverb'> | null>(null);
	const [isConnected, setIsConnected] = useState(false);
	const { token } = useAuth();

	useEffect(() => {
		// If no token, we might skip connection or connect to public-only channels
		if (!token) return;

		const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
		const wsPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT, 10) : 8081;

		const echoInstance = new Echo<'reverb'>({
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
				headers: {
					Authorization: `Bearer ${token}`,
					Accept: 'application/json',
				},
			},
		});

		echoInstance.connector.pusher.connection.bind('connected', () => setIsConnected(true));
		echoInstance.connector.pusher.connection.bind('disconnected', () => setIsConnected(false));
		echoInstance.connector.pusher.connection.bind('unavailable', () => setIsConnected(false));

		setEcho(echoInstance);

		return () => {
			echoInstance.disconnect();
		};
	}, [token]);

	return <SocketContext.Provider value={{ echo, isConnected }}>{children}</SocketContext.Provider>;
};

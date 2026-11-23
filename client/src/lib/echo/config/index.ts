import Echo, { type BroadcastDriver, type EchoOptions } from 'laravel-echo';
import Pusher from 'pusher-js';
import type { ConfigDefaults } from '../types';

/**
 * Build an Echo instance. A plain factory, on purpose (#253): the provider owns the instance
 * and hands it down through React context, so there is no module-level singleton that a
 * subscriber can read before it is configured and then never see change.
 *
 * Lazy-loaded by the provider so `laravel-echo` and `pusher-js` stay out of the main bundle.
 */
export const createEcho = <T extends BroadcastDriver>(config: EchoOptions<T>): Echo<T> => {
	const defaults: ConfigDefaults<BroadcastDriver> = {
		reverb: {
			broadcaster: 'reverb',
			key: import.meta.env.VITE_REVERB_APP_KEY,
			wsHost: import.meta.env.VITE_REVERB_HOST,
			wsPort: import.meta.env.VITE_REVERB_PORT,
			wssPort: import.meta.env.VITE_REVERB_PORT,
			forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
			enabledTransports: ['ws', 'wss'],
		},
		pusher: {
			broadcaster: 'pusher',
			key: import.meta.env.VITE_PUSHER_APP_KEY,
			cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
			forceTLS: true,
			wsHost: import.meta.env.VITE_PUSHER_HOST,
			wsPort: import.meta.env.VITE_PUSHER_PORT,
			wssPort: import.meta.env.VITE_PUSHER_PORT,
			enabledTransports: ['ws', 'wss'],
		},
		'socket.io': {
			broadcaster: 'socket.io',
			host: import.meta.env.VITE_SOCKET_IO_HOST,
		},
		null: {
			broadcaster: 'null',
		},
		ably: {
			broadcaster: 'pusher',
			key: import.meta.env.VITE_ABLY_PUBLIC_KEY,
			wsHost: 'realtime-pusher.ably.io',
			wsPort: 443,
			disableStats: true,
			encrypted: true,
		},
	};

	const merged = {
		...defaults[config.broadcaster],
		...config,
	} as EchoOptions<BroadcastDriver>;

	merged.Pusher ??= Pusher;

	return new Echo(merged) as unknown as Echo<T>;
};

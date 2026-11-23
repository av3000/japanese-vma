import type { Decorator } from '@storybook/react-vite';
import { DEFAULT_SOCKET_CONTEXT, SocketContext, type SocketContextType } from '@/providers/contexts/socket-provider';

export type SocketScenario = 'unconfigured' | 'connecting' | 'connected' | 'reconnecting' | 'failed';

/** A scenario name, or fields to override (a fake `echo`, say) on top of the toolbar's scenario. */
export type SocketParameter = SocketScenario | Partial<SocketContextType>;

const configured: SocketContextType = { ...DEFAULT_SOCKET_CONTEXT, isConfigured: true, hasAttemptedConnection: true };

export const socketScenarios: Record<SocketScenario, SocketContextType> = {
	unconfigured: DEFAULT_SOCKET_CONTEXT,
	connecting: { ...configured, connectionStatus: 'connecting' },
	connected: { ...configured, connectionStatus: 'connected', isConnected: true, generation: 1 },
	reconnecting: { ...configured, connectionStatus: 'reconnecting' },
	failed: { ...configured, connectionStatus: 'failed', lastError: 'Connection refused by host' },
};

export const resolveSocket = (parameter: SocketParameter | undefined, scenario: SocketScenario): SocketContextType => {
	if (typeof parameter === 'string') {
		return socketScenarios[parameter];
	}

	return { ...socketScenarios[scenario], ...parameter };
};

/**
 * Provides `SocketContext` with a plain value; `echo` stays `null` unless a story passes a fake.
 * `parameters.socket` wins over the toolbar's `socket` global.
 */
export const withSocket: Decorator = (Story, { parameters, globals }) => (
	<SocketContext.Provider
		value={resolveSocket(
			parameters.socket as SocketParameter | undefined,
			(globals.socket as SocketScenario) ?? 'connected',
		)}
	>
		<Story />
	</SocketContext.Provider>
);

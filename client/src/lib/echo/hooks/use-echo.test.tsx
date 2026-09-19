/**
 * @vitest-environment jsdom
 */
import type Echo from 'laravel-echo';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuth } from '@/hooks/useAuth';
import { createEcho } from '@/lib/echo/config/index';
import { DEFAULT_SOCKET_CONTEXT, SocketContext, WebSocketProvider } from '@/providers/contexts/socket-provider';
import { renderWithAct } from '@/test/renderWithAct';
import { useEcho } from './use-echo';

vi.mock('@/hooks/useAuth', () => ({
	useAuth: vi.fn(),
}));

vi.mock('@/lib/echo/config/index', () => ({
	createEcho: vi.fn(),
}));

/** Just enough of an Echo instance for the provider and the hook to drive. */
const fakeEcho = () => {
	const channel = {
		listen: vi.fn(),
		stopListening: vi.fn(),
		subscribed: vi.fn(),
		error: vi.fn(),
	};
	const connection = { bind: vi.fn(), unbind: vi.fn(), state: 'connected' };
	const instance = {
		private: vi.fn(() => channel),
		channel: vi.fn(() => channel),
		join: vi.fn(() => channel),
		leave: vi.fn(),
		leaveChannel: vi.fn(),
		disconnect: vi.fn(),
		connector: { pusher: { connection } },
	};

	return { instance: instance as unknown as Echo<'reverb'>, spies: instance, channel };
};

const Subscriber = ({ onEvent = () => {} }: { onEvent?: (payload: unknown) => void }) => {
	useEcho('last_operations.abc', '.OperationStatusUpdated', onEvent, [], 'private');
	return null;
};

describe('useEcho is reactive to the provider configuring Echo', () => {
	beforeEach(() => {
		vi.stubEnv('VITE_REVERB_APP_KEY', 'test-key');
		vi.stubEnv('VITE_API_URL', 'http://api.test');
	});

	afterEach(() => {
		vi.unstubAllEnvs();
		vi.mocked(createEcho).mockReset();
		vi.mocked(useAuth).mockReset();
	});

	it('subscribes only once the instance exists, not on first render', async () => {
		const { instance, spies, channel } = fakeEcho();

		const rendered = await renderWithAct(
			<SocketContext.Provider value={{ ...DEFAULT_SOCKET_CONTEXT, echo: null, generation: 0 }}>
				<Subscriber />
			</SocketContext.Provider>,
		);
		expect(spies.private).not.toHaveBeenCalled();

		await rendered.flush(() => {
			rendered.rerender(
				<SocketContext.Provider value={{ ...DEFAULT_SOCKET_CONTEXT, echo: instance, generation: 1 }}>
					<Subscriber />
				</SocketContext.Provider>,
			);
		});

		expect(spies.private).toHaveBeenCalledTimes(1);
		expect(spies.private).toHaveBeenCalledWith('last_operations.abc');
		expect(channel.listen).toHaveBeenCalledWith('.OperationStatusUpdated', expect.any(Function));

		await rendered.unmount();
		expect(channel.stopListening).toHaveBeenCalledWith('.OperationStatusUpdated', expect.any(Function));
		expect(spies.leave).toHaveBeenCalledWith('last_operations.abc');
	});

	it('shares one subscription between two listeners on the same channel', async () => {
		const { instance, spies } = fakeEcho();

		const rendered = await renderWithAct(
			<SocketContext.Provider value={{ ...DEFAULT_SOCKET_CONTEXT, echo: instance, generation: 1 }}>
				<Subscriber />
				<Subscriber />
			</SocketContext.Provider>,
		);

		expect(spies.private).toHaveBeenCalledTimes(1);

		await rendered.unmount();
		expect(spies.leave).toHaveBeenCalledTimes(1);
	});

	it('a token change disconnects the old instance and subscribes the new one exactly once', async () => {
		const first = fakeEcho();
		const second = fakeEcho();
		vi.mocked(createEcho).mockReturnValueOnce(first.instance).mockReturnValueOnce(second.instance);
		vi.mocked(useAuth).mockReturnValue({ isAuthenticated: true, token: 'token-a' } as never);

		const rendered = await renderWithAct(
			<WebSocketProvider>
				<Subscriber />
			</WebSocketProvider>,
		);
		// The provider configures after a dynamic import; flush the microtasks it awaits.
		await rendered.flush(async () => {
			await Promise.resolve();
		});

		expect(createEcho).toHaveBeenCalledTimes(1);
		expect(first.spies.private).toHaveBeenCalledTimes(1);
		expect(second.spies.private).not.toHaveBeenCalled();

		vi.mocked(useAuth).mockReturnValue({ isAuthenticated: true, token: 'token-b' } as never);
		await rendered.flush(() => {
			rendered.rerender(
				<WebSocketProvider>
					<Subscriber />
				</WebSocketProvider>,
			);
		});
		await rendered.flush(async () => {
			await Promise.resolve();
		});

		expect(first.spies.disconnect).toHaveBeenCalledTimes(1);
		expect(createEcho).toHaveBeenCalledTimes(2);
		expect(second.spies.private).toHaveBeenCalledTimes(1);
		expect(first.spies.private).toHaveBeenCalledTimes(1);
		expect(vi.mocked(createEcho).mock.calls[1][0]).toMatchObject({
			auth: { headers: { Authorization: 'Bearer token-b' } },
		});
		expect(vi.mocked(createEcho).mock.calls[1][0]).not.toHaveProperty('cluster');

		await rendered.unmount();
		expect(second.spies.disconnect).toHaveBeenCalledTimes(1);
	});
});

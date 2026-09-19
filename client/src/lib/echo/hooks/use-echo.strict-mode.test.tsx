/**
 * @vitest-environment jsdom
 */
import React from 'react';
import type Echo from 'laravel-echo';
import { describe, expect, it, vi } from 'vitest';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import { renderWithAct } from '@/test/renderWithAct';
import { channelRegistrySizeFor, useEcho } from './use-echo';

const fakeEcho = () => {
	const channel = { listen: vi.fn(), stopListening: vi.fn(), subscribed: vi.fn(), error: vi.fn() };
	const instance = {
		private: vi.fn(() => channel),
		channel: vi.fn(() => channel),
		join: vi.fn(() => channel),
		leave: vi.fn(),
		leaveChannel: vi.fn(),
		disconnect: vi.fn(),
		connector: { pusher: { connection: { bind: vi.fn(), unbind: vi.fn(), state: 'connected' } } },
	};

	return { instance: instance as unknown as Echo<'reverb'>, spies: instance };
};

const Listener = ({ tick }: { tick: number }) => {
	useEcho('last_operations.shared', '.OperationStatusUpdated', () => {}, [tick], 'private');

	return <span data-tick={tick} />;
};

/**
 * Issue #255: the refcount must balance under StrictMode's double-invoked effects and across
 * re-renders, so the registry ends empty and the socket channel is left exactly once.
 */
describe('useEcho refcount under React.StrictMode', () => {
	it('leaves the channel once after two listeners re-render five times each and unmount', async () => {
		const { instance, spies } = fakeEcho();

		const tree = (tick: number) => (
			<React.StrictMode>
				<SocketContext.Provider value={{ ...DEFAULT_SOCKET_CONTEXT, echo: instance, generation: 1 }}>
					<Listener tick={tick} />
					<Listener tick={tick} />
				</SocketContext.Provider>
			</React.StrictMode>
		);

		const rendered = await renderWithAct(tree(0));

		for (let tick = 1; tick <= 5; tick += 1) {
			await rendered.rerender(tree(tick));
		}

		expect(spies.private).toHaveBeenCalledTimes(1);
		expect(spies.leave).not.toHaveBeenCalled();
		expect(channelRegistrySizeFor(instance as never)).toBe(1);

		await rendered.unmount();

		expect(channelRegistrySizeFor(instance as never)).toBe(0);
		expect(spies.leave).toHaveBeenCalledTimes(1);
		expect(spies.leave).toHaveBeenCalledWith('last_operations.shared');
	});
});

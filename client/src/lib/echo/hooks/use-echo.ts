import { type DependencyList, useCallback, useEffect, useMemo, useRef } from 'react';
import type Echo from 'laravel-echo';
import { type BroadcastDriver } from 'laravel-echo';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import type {
	BroadcastNotification,
	Channel,
	ChannelData,
	ChannelReturnType,
	Connection,
	ConnectionStatus,
	EventName,
	InferEventPayload,
	ModelEvents,
	ModelPayload,
} from '../types';
import { toArray } from '../util';

/**
 * Channel subscriptions are shared per Echo instance and reference-counted, so two components
 * listening on the same channel open one socket subscription. Keying the cache on the instance
 * (a WeakMap) means a new instance after reconfiguration never sees a stale entry (#253).
 */
const channelsByInstance = new WeakMap<Echo<BroadcastDriver>, Map<string, ChannelData<BroadcastDriver>>>();

/**
 * Releases are deferred to a microtask. React StrictMode runs effect, cleanup, effect for a
 * mount in one synchronous pass; without the deferral the cleanup would leave the channel and
 * the second effect would open a second subscription. A re-acquire inside the same task
 * cancels the pending release, so every acquire still pairs with exactly one leave (#255).
 */
const pendingReleases = new WeakMap<Echo<BroadcastDriver>, Set<string>>();

/** Test seam: how many channels the registry currently holds for an instance. */
export const channelRegistrySizeFor = (instance: Echo<BroadcastDriver>): number =>
	channelsByInstance.get(instance)?.size ?? 0;

const channelsFor = (instance: Echo<BroadcastDriver>): Map<string, ChannelData<BroadcastDriver>> => {
	let channels = channelsByInstance.get(instance);

	if (!channels) {
		channels = new Map();
		channelsByInstance.set(instance, channels);
	}

	return channels;
};

const createNoopConnection = <T extends BroadcastDriver>(): Connection<T> => {
	const noopConnection = {
		listen: () => noopConnection,
		stopListening: () => noopConnection,
		listenForWhisper: () => noopConnection,
		stopListeningForWhisper: () => noopConnection,
		subscribed: () => noopConnection,
		error: () => noopConnection,
		on: () => noopConnection,
		here: () => noopConnection,
		joining: () => noopConnection,
		leaving: () => noopConnection,
		whisper: () => noopConnection,
		notification: () => noopConnection,
		stopListeningForNotification: () => noopConnection,
		unsubscribe: () => undefined,
	};

	return noopConnection as unknown as Connection<T>;
};

const subscribeToChannel = <T extends BroadcastDriver>(
	instance: Echo<BroadcastDriver>,
	channel: Channel,
): Connection<T> => {
	if (channel.visibility === 'presence') {
		return instance.join(channel.name) as unknown as Connection<T>;
	}

	if (channel.visibility === 'private') {
		return instance.private(channel.name) as unknown as Connection<T>;
	}

	return instance.channel(channel.name) as unknown as Connection<T>;
};

const acquireChannel = <T extends BroadcastDriver>(
	instance: Echo<BroadcastDriver>,
	channel: Channel,
): Connection<T> => {
	const channels = channelsFor(instance);
	const existing = channels.get(channel.id);

	if (existing) {
		existing.count += 1;
		pendingReleases.get(instance)?.delete(channel.id);

		return existing.connection as Connection<T>;
	}

	const connection = subscribeToChannel<T>(instance, channel);
	channels.set(channel.id, { count: 1, connection });

	return connection;
};

const releaseChannel = (instance: Echo<BroadcastDriver>, channel: Channel): void => {
	const channels = channelsFor(instance);
	const entry = channels.get(channel.id);

	if (!entry) {
		return;
	}

	entry.count -= 1;

	if (entry.count > 0) {
		return;
	}

	let pending = pendingReleases.get(instance);
	if (!pending) {
		pending = new Set();
		pendingReleases.set(instance, pending);
	}
	pending.add(channel.id);

	queueMicrotask(() => {
		if (!pending.has(channel.id)) {
			return; // re-acquired before the release ran
		}

		pending.delete(channel.id);

		const current = channels.get(channel.id);
		if (!current || current.count > 0) {
			return;
		}

		channels.delete(channel.id);
		instance.leave(channel.name);
	});
};

export function useEcho<
	TEvent extends EventName = EventName,
	TDriver extends BroadcastDriver = BroadcastDriver,
	TVisibility extends Channel['visibility'] = 'private',
>(
	channelName: string,
	event: TEvent | TEvent[],
	callback: (payload: InferEventPayload<TEvent>) => void,
	dependencies?: DependencyList,
	visibility?: TVisibility,
): {
	leaveChannel: () => void;
	leave: () => void;
	stopListening: () => void;
	listen: () => void;
	channel: () => ChannelReturnType<TDriver, TVisibility>;
};

export function useEcho<
	TPayload,
	TDriver extends BroadcastDriver = BroadcastDriver,
	TVisibility extends Channel['visibility'] = 'private',
>(
	channelName: string,
	event: string | string[],
	callback: (payload: TPayload) => void,
	dependencies?: DependencyList,
	visibility?: TVisibility,
): {
	leaveChannel: () => void;
	leave: () => void;
	stopListening: () => void;
	listen: () => void;
	channel: () => ChannelReturnType<TDriver, TVisibility>;
};

/**
 * Subscribe to a channel on the provider's Echo instance and listen for events.
 *
 * Reactive to configuration (#253): nothing is subscribed until the provider has an instance,
 * and when the instance or its generation changes the old subscription is torn down and a
 * new one opened. Before the instance exists `channel()` returns a no-op connection.
 */
export function useEcho<
	TPayload,
	TDriver extends BroadcastDriver = BroadcastDriver,
	TVisibility extends Channel['visibility'] = 'private',
>(
	channelName: string,
	event: string | string[] = [],
	callback: (payload: TPayload) => void = () => {},
	dependencies: DependencyList = [],
	visibility: TVisibility = 'private' as TVisibility,
) {
	const { echo, generation } = useWebSocket();

	const channel: Channel = useMemo(
		() => ({
			name: channelName,
			id: ['private', 'presence'].includes(visibility) ? `${visibility}-${channelName}` : channelName,
			visibility,
		}),
		[channelName, visibility],
	);

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const callbackFunc = useCallback(callback, dependencies);

	const eventKey = Array.isArray(event) ? JSON.stringify(event) : event;
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const events = useMemo(() => toArray(event), [eventKey]);

	const subscription = useRef<Connection<TDriver> | null>(null);
	const listening = useRef(false);

	const stopListening = useCallback(() => {
		if (!listening.current || !subscription.current) {
			return;
		}

		events.forEach((e) => {
			subscription.current?.stopListening(e, callbackFunc);
		});

		listening.current = false;
	}, [events, callbackFunc]);

	const listen = useCallback(() => {
		if (listening.current || !subscription.current) {
			return;
		}

		events.forEach((e) => {
			subscription.current?.listen(e, callbackFunc);
		});

		listening.current = true;
	}, [events, callbackFunc]);

	const tearDown = useCallback(() => {
		stopListening();

		if (echo && subscription.current) {
			releaseChannel(echo as Echo<BroadcastDriver>, channel);
		}

		subscription.current = null;
	}, [stopListening, echo, channel]);

	const leave = useCallback(() => {
		tearDown();
	}, [tearDown]);

	useEffect(() => {
		if (!echo) {
			return;
		}

		subscription.current = acquireChannel<TDriver>(echo as Echo<BroadcastDriver>, channel);
		listen();

		return () => {
			tearDown();
		};
		// `generation` is intentionally a dependency: a new instance of the same identity must
		// still resubscribe.
	}, [echo, generation, channel, listen, tearDown]);

	return useMemo(
		() => ({
			leaveChannel: () => tearDown(),
			leave,
			stopListening,
			listen,
			channel: () =>
				(subscription.current ?? createNoopConnection<TDriver>()) as ChannelReturnType<TDriver, TVisibility>,
		}),
		[leave, listen, stopListening, tearDown],
	);
}

export const useEchoNotification = <TPayload, TDriver extends BroadcastDriver = BroadcastDriver>(
	channelName: string,
	callback: (payload: BroadcastNotification<TPayload>) => void = () => {},
	event: string | string[] = [],
	dependencies: DependencyList = [],
) => {
	const result = useEcho<BroadcastNotification<TPayload>, TDriver, 'private'>(
		channelName,
		[],
		callback,
		dependencies,
		'private',
	);

	const eventKey = Array.isArray(event) ? JSON.stringify(event) : event;
	const events = useMemo(() => {
		return toArray(event)
			.map((e) => {
				if (e.includes('.')) {
					return [e, e.replace(/\./g, '\\')];
				}

				return [e, e.replace(/\\/g, '.')];
			})
			.flat();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [eventKey]);

	const listening = useRef(false);

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const memoizedCallback = useCallback(callback, dependencies);

	const cb = useCallback(
		(notification: BroadcastNotification<TPayload>) => {
			if (!listening.current) {
				return;
			}

			if (events.length === 0 || events.includes(notification.type)) {
				memoizedCallback(notification);
			}
		},
		[memoizedCallback, events],
	);

	const listen = useCallback(() => {
		if (listening.current) {
			return;
		}

		result.channel().notification(cb);
		listening.current = true;
	}, [cb, result]);

	const stopListening = useCallback(() => {
		if (!listening.current) {
			return;
		}

		result.channel().stopListeningForNotification(cb);
		listening.current = false;
	}, [cb, result]);

	useEffect(() => {
		listen();

		return () => stopListening();
	}, [listen, stopListening]);

	return useMemo(
		() => ({
			...result,
			stopListening,
			listen,
		}),
		[result, stopListening, listen],
	);
};

export const useEchoPresence = <TPayload, TDriver extends BroadcastDriver = BroadcastDriver>(
	channelName: string,
	event: string | string[] = [],
	callback: (payload: TPayload) => void = () => {},
	dependencies: DependencyList = [],
) => {
	return useEcho<TPayload, TDriver, 'presence'>(channelName, event, callback, dependencies, 'presence');
};

export const useEchoPublic = <TPayload, TDriver extends BroadcastDriver = BroadcastDriver>(
	channelName: string,
	event: string | string[] = [],
	callback: (payload: TPayload) => void = () => {},
	dependencies: DependencyList = [],
) => {
	return useEcho<TPayload, TDriver, 'public'>(channelName, event, callback, dependencies, 'public');
};

export const useEchoModel = <TPayload, TModel extends string, TDriver extends BroadcastDriver = BroadcastDriver>(
	model: TModel,
	identifier: string | number,
	event: ModelEvents<TModel> | ModelEvents<TModel>[] = [],
	callback: (payload: ModelPayload<TPayload>) => void = () => {},
	dependencies: DependencyList = [],
) => {
	return useEcho<ModelPayload<TPayload>, TDriver, 'private'>(
		`${model}.${identifier}`,
		toArray(event).map((e) => (e.startsWith('.') ? e : `.${e}`)),
		callback,
		dependencies,
		'private',
	);
};

/** The provider already tracks the Pusher connection state; read it from context. */
export const useConnectionStatus = (): ConnectionStatus => useWebSocket().connectionStatus;

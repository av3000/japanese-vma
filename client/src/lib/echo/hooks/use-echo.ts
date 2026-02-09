import { type DependencyList, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { type BroadcastDriver } from 'laravel-echo';
import { echo } from '../config';
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

const channels = new Map<string, ChannelData<BroadcastDriver>>();

const getPusherConnection = (
	connector: unknown,
): { bind: (event: string, callback: (payload: unknown) => void) => void; unbind: (event: string, callback: (payload: unknown) => void) => void; state?: string } | null => {
	if (!connector || typeof connector !== 'object') {
		return null;
	}

	if (!('pusher' in connector)) {
		return null;
	}

	const pusher = (connector as { pusher?: unknown }).pusher;
	if (!pusher || typeof pusher !== 'object') {
		return null;
	}

	if (!('connection' in pusher)) {
		return null;
	}

	const connection = (pusher as { connection?: unknown }).connection;
	if (!connection || typeof connection !== 'object') {
		return null;
	}

	const hasBind = 'bind' in connection && typeof (connection as { bind?: unknown }).bind === 'function';
	const hasUnbind = 'unbind' in connection && typeof (connection as { unbind?: unknown }).unbind === 'function';

	if (!hasBind || !hasUnbind) {
		return null;
	}

	return connection as {
		bind: (event: string, callback: (payload: unknown) => void) => void;
		unbind: (event: string, callback: (payload: unknown) => void) => void;
		state?: string;
	};
};

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

const subscribeToChannel = <T extends BroadcastDriver>(channel: Channel): Connection<T> => {
	const instance = echo<T>();

	if (channel.visibility === 'presence') {
		return instance.join(channel.name);
	}

	if (channel.visibility === 'private') {
		return instance.private(channel.name);
	}

	return instance.channel(channel.name);
};

const leaveChannel = (channel: Channel, leaveAll: boolean): void => {
	const channelEntry = channels.get(channel.id);

	if (!channelEntry) {
		return;
	}

	channelEntry.count -= 1;

	if (channelEntry.count > 0) {
		return;
	}

	if (leaveAll) {
		echo().leave(channel.name);
	} else {
		echo().leaveChannel(channel.id);
	}

	channels.delete(channel.id);
};

const resolveChannelSubscription = <T extends BroadcastDriver>(channel: Channel): Connection<T> => {
	const existingChannel = channels.get(channel.id);

	if (existingChannel) {
		existingChannel.count += 1;

		return existingChannel.connection;
	}

	const channelSubscription = subscribeToChannel<T>(channel);

	channels.set(channel.id, {
		count: 1,
		connection: channelSubscription,
	});

	return channelSubscription;
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
	leaveChannel: (leaveAll?: boolean) => void;
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
	leaveChannel: (leaveAll?: boolean) => void;
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
	event: string | string[] = [],
	callback: (payload: TPayload) => void = () => {},
	dependencies: DependencyList = [],
	visibility: TVisibility = 'private' as TVisibility,
) {
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
	const listening = useRef(false);
	const initialized = useRef(false);
	const subscription = useRef<Connection<TDriver>>(resolveChannelSubscription<TDriver>(channel));

	const eventKey = Array.isArray(event) ? JSON.stringify(event) : event;
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const events = useMemo(() => toArray(event), [eventKey]);

	const stopListening = useCallback(() => {
		if (!listening.current) {
			return;
		}

		events.forEach((e) => {
			subscription.current.stopListening(e, callbackFunc);
		});

		listening.current = false;
	}, [events, callbackFunc]);

	const listen = useCallback(() => {
		if (listening.current) {
			return;
		}

		events.forEach((e) => {
			subscription.current.listen(e, callbackFunc);
		});

		listening.current = true;
	}, [events, callbackFunc]);

	const tearDown = useCallback(
		(leaveAll: boolean = false) => {
			stopListening();

			leaveChannel(channel, leaveAll);
		},
		[stopListening, channel],
	);

	const leave = useCallback(() => {
		tearDown(true);
	}, [tearDown]);

	useEffect(() => {
		if (initialized.current) {
			subscription.current = resolveChannelSubscription<TDriver>(channel);
		}

		initialized.current = true;

		listen();

		return tearDown;
	}, [listen, tearDown, channel]);

	return useMemo(
		() => ({
			leaveChannel: tearDown,
			leave,
			stopListening,
			listen,
			channel: () => subscription.current as ChannelReturnType<TDriver, TVisibility>,
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
	const initializedRef = useRef(false);

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

		if (!initializedRef.current) {
			result.channel().notification(cb);
		}

		listening.current = true;
		initializedRef.current = true;
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

export const useConnectionStatus = (): ConnectionStatus => {
	const getConnectionStatus = (): ConnectionStatus => {
		const connection = getPusherConnection(echo().connector);
		if (!connection) {
			return 'disconnected';
		}

		return mapPusherState(connection.state);
	};

	const [status, setStatus] = useState<ConnectionStatus>(() => getConnectionStatus());

	useEffect(() => {
		const connection = getPusherConnection(echo().connector);
		if (!connection) {
			setStatus('disconnected');
			return;
		}

		const handleStateChange = (payload: unknown) => {
			if (typeof payload === 'string') {
				setStatus(mapPusherState(payload));
				return;
			}

			if (payload && typeof payload === 'object' && 'current' in payload) {
				const current = (payload as { current?: unknown }).current;
				if (typeof current === 'string') {
					setStatus(mapPusherState(current));
				}
			}
		};

		connection.bind('state_change', handleStateChange);
		setStatus(mapPusherState(connection.state));

		return () => {
			connection.unbind('state_change', handleStateChange);
		};
	}, []);

	return status;
};

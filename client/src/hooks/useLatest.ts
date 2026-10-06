import { useLayoutEffect, useRef, type RefObject } from 'react';

/**
 * A ref that always holds the latest value. For long-lived listeners (window keydown,
 * subscriptions) that must call the current callback without re-subscribing on every
 * parent render.
 */
export const useLatest = <T>(value: T): RefObject<T> => {
	const ref = useRef(value);

	useLayoutEffect(() => {
		ref.current = value;
	});

	return ref;
};

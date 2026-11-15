import { useCallback, useEffect, useState } from 'react';

/** How long a search box waits after the last keystroke before it asks the server. */
export const SEARCH_DEBOUNCE_MS = 300;

/**
 * A value that trails `value` by `delayMs`. `flush` catches it up at once, for an explicit
 * submit while the user is still inside the delay.
 */
export const useDebouncedValue = <T>(value: T, delayMs: number) => {
	const [debounced, setDebounced] = useState(value);

	useEffect(() => {
		const timeoutId = window.setTimeout(() => setDebounced(value), delayMs);

		return () => window.clearTimeout(timeoutId);
	}, [value, delayMs]);

	const flush = useCallback(() => setDebounced(value), [value]);

	return [debounced, flush] as const;
};

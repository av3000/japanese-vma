import { useCallback, useEffect, useRef, useState } from 'react';
import { useDebouncedValue } from '@/hooks/useDebouncedValue';

/** How long the dashboard waits after the last keystroke before it searches. */
export const SEARCH_DEBOUNCE_MS = 300;

/**
 * Type-ahead search whose applied value lives in the URL. The input edits a local draft; after
 * a pause (or at once on Enter) the draft is committed, which the caller writes to the URL. When
 * the URL changes from outside (back/forward, a tab link), the draft follows it.
 *
 * The raw text is committed, spaces included: trimming here would strip a trailing space while
 * the user is still typing. The request layer trims and enforces the minimum length.
 */
export const useDashboardKeyword = (urlValue: string, commit: (value: string) => void) => {
	const [draft, setDraft] = useState(urlValue);
	const [debounced, flush] = useDebouncedValue(draft, SEARCH_DEBOUNCE_MS);
	const committed = useRef(urlValue);
	const commitRef = useRef(commit);
	commitRef.current = commit;

	useEffect(() => {
		if (urlValue !== committed.current) {
			committed.current = urlValue;
			setDraft(urlValue);
		}
	}, [urlValue]);

	useEffect(() => {
		if (debounced !== committed.current) {
			committed.current = debounced;
			commitRef.current(debounced);
		}
	}, [debounced]);

	/** Enter, or the search button: apply what is typed without waiting out the pause. */
	const applyNow = useCallback(() => flush(), [flush]);

	return { draft, setDraft, applyNow };
};

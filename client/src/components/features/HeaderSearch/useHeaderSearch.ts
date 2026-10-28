import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { useSearchCounts } from '@/api/search/useSearchCounts';
import { SEARCH_DEBOUNCE_MS, useDebouncedValue } from '@/hooks/useDebouncedValue';
import { resolveScope, type ScopeCounts, type ScopeResolution } from './detectScope';
import { readSearchMemory, rememberSearch, type RecentSearch } from './recentSearches';
import { normalizeRefine, SCOPE_REFINES, scopedSearchUrl, type SearchScope } from './searchScopes';

export interface HeaderSearchState {
	query: string;
	hasQuery: boolean;
	resolution: ScopeResolution;
	/** Hits per scope for the current query, or `null` while they load, after a failure, or with no query. */
	counts: ScopeCounts | null;
	/** Index into `resolution.order` of the highlighted row; what Enter runs when there is a query. */
	activeIndex: number;
	/** Refine values chosen for the current scope. */
	refine: string[];
	recent: RecentSearch[];
	setQuery: (value: string) => void;
	pickScope: (scope: SearchScope) => void;
	toggleRefine: (value: string) => void;
	clearRefine: () => void;
	moveActive: (delta: 1 | -1) => void;
	/** Navigates to `scope` (by default the highlighted row, or the current scope without a query). */
	run: (scope?: SearchScope) => void;
	runRecent: (entry: RecentSearch) => void;
}

/**
 * The Header search state shared by the desktop panel and the mobile drawer. Every choice
 * navigates to the scope's existing list URL, which does the searching; the only request here is
 * the per-scope hit counts for the debounced query, which order the rows and pick the best match.
 * Without counts (loading or failed) detection decides alone. `onDone` runs after each navigation
 * so the caller can close its panel or drawer.
 */
export const useHeaderSearch = (onDone?: () => void): HeaderSearchState => {
	const navigate = useNavigate();
	const [memory, setMemory] = React.useState(() => readSearchMemory());
	const [query, setQueryValue] = React.useState('');
	const [picked, setPicked] = React.useState<SearchScope | null>(null);
	const [refineByScope, setRefineByScope] = React.useState<Partial<Record<SearchScope, string[]>>>({});
	// The highlighted row is remembered by scope, not position, so it stays put when counts
	// arrive and move the best match to the top. `null` is the first row.
	const [activeScope, setActiveScope] = React.useState<SearchScope | null>(null);
	const [debouncedQuery] = useDebouncedValue(query, SEARCH_DEBOUNCE_MS);
	const fetched = useSearchCounts(debouncedQuery);
	// Counts belong to the query they were asked for; while the reader types on, none show.
	const counts = debouncedQuery.trim() === query.trim() ? fetched.counts : null;

	const resolution = React.useMemo(
		() => resolveScope({ query, picked, lastScope: memory.lastScope, counts }),
		[query, picked, memory.lastScope, counts],
	);
	const hasQuery = query.trim() !== '';
	const activeIndex = activeScope === null ? 0 : Math.max(0, resolution.order.indexOf(activeScope));
	const scope = resolution.scope;
	const refine = refineByScope[scope] ?? [];

	const setQuery = (value: string) => {
		setQueryValue(value);
		if (value.trim() === '') setPicked(null);
		setActiveScope(null);
	};

	const pickScope = (next: SearchScope) => {
		setPicked((current) => (current === next ? null : next));
		setActiveScope(null);
	};

	const toggleRefine = (value: string) => {
		const definition = SCOPE_REFINES[scope];
		if (!definition) return;
		const has = refine.includes(value);
		let next: string[];
		if (definition.multiple) next = has ? refine.filter((current) => current !== value) : [...refine, value];
		else next = has ? [] : [value];
		setRefineByScope((current) => ({ ...current, [scope]: normalizeRefine(scope, next) }));
	};

	const clearRefine = () => setRefineByScope((current) => ({ ...current, [scope]: [] }));

	const moveActive = (delta: 1 | -1) => {
		if (!hasQuery) return;
		const count = resolution.order.length;
		setActiveScope(resolution.order[(activeIndex + delta + count) % count]);
	};

	const finish = (target: SearchScope, keyword: string, values: readonly string[]) => {
		setMemory(rememberSearch(target, keyword));
		setQueryValue('');
		setPicked(null);
		setActiveScope(null);
		navigate(scopedSearchUrl(target, keyword, values));
		onDone?.();
	};

	const run = (target: SearchScope = hasQuery ? resolution.order[activeIndex] : scope) =>
		finish(target, query, refineByScope[target] ?? []);

	const runRecent = (entry: RecentSearch) => finish(entry.scope, entry.query, []);

	return {
		query,
		hasQuery,
		resolution,
		counts,
		activeIndex,
		refine,
		recent: memory.recent,
		setQuery,
		pickScope,
		toggleRefine,
		clearRefine,
		moveActive,
		run,
		runRecent,
	};
};

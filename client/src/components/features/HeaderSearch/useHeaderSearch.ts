import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { resolveScope, type ScopeResolution } from './detectScope';
import { readSearchMemory, rememberSearch, type RecentSearch } from './recentSearches';
import { normalizeRefine, SCOPE_REFINES, scopedSearchUrl, type SearchScope } from './searchScopes';

export interface HeaderSearchState {
	query: string;
	hasQuery: boolean;
	resolution: ScopeResolution;
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
 * The Header search state shared by the desktop panel and the mobile drawer. Nothing is fetched:
 * every choice navigates to the scope's existing list URL, which does the searching. `onDone` runs
 * after each navigation so the caller can close its panel or drawer.
 */
export const useHeaderSearch = (onDone?: () => void): HeaderSearchState => {
	const navigate = useNavigate();
	const [memory, setMemory] = React.useState(() => readSearchMemory());
	const [query, setQueryValue] = React.useState('');
	const [picked, setPicked] = React.useState<SearchScope | null>(null);
	const [refineByScope, setRefineByScope] = React.useState<Partial<Record<SearchScope, string[]>>>({});
	const [activeIndex, setActiveIndex] = React.useState(0);

	const resolution = React.useMemo(
		() => resolveScope({ query, picked, lastScope: memory.lastScope }),
		[query, picked, memory.lastScope],
	);
	const hasQuery = query.trim() !== '';
	const scope = resolution.scope;
	const refine = refineByScope[scope] ?? [];

	const setQuery = (value: string) => {
		setQueryValue(value);
		if (value.trim() === '') setPicked(null);
		setActiveIndex(0);
	};

	const pickScope = (next: SearchScope) => {
		setPicked((current) => (current === next ? null : next));
		setActiveIndex(0);
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
		setActiveIndex((current) => (current + delta + count) % count);
	};

	const finish = (target: SearchScope, keyword: string, values: readonly string[]) => {
		setMemory(rememberSearch(target, keyword));
		setQueryValue('');
		setPicked(null);
		setActiveIndex(0);
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

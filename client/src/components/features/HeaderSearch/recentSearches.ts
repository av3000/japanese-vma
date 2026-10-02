import { DEFAULT_SEARCH_SCOPE, isSearchScope, type SearchScope } from './searchScopes';

export const SEARCH_MEMORY_KEY = 'jplearning.headerSearch';

export const MAX_RECENT_SEARCHES = 5;

export interface RecentSearch {
	scope: SearchScope;
	query: string;
}

/** What the Header search remembers between visits: the last scope searched and the last queries. */
export interface SearchMemory {
	lastScope: SearchScope;
	recent: RecentSearch[];
}

const EMPTY_MEMORY: SearchMemory = { lastScope: DEFAULT_SEARCH_SCOPE, recent: [] };

/** `localStorage` can be missing, blocked by privacy settings, or throw on access. */
const getStorage = (): Storage | null => {
	try {
		return typeof window === 'undefined' ? null : window.localStorage;
	} catch {
		return null;
	}
};

const isRecentSearch = (value: unknown): value is RecentSearch => {
	if (typeof value !== 'object' || value === null) return false;
	const entry = value as Record<string, unknown>;
	return isSearchScope(entry.scope) && typeof entry.query === 'string' && entry.query.trim() !== '';
};

export const readSearchMemory = (storage: Storage | null = getStorage()): SearchMemory => {
	try {
		const raw = storage?.getItem(SEARCH_MEMORY_KEY);
		if (!raw) return EMPTY_MEMORY;
		const parsed = JSON.parse(raw) as Partial<Record<keyof SearchMemory, unknown>>;

		return {
			lastScope: isSearchScope(parsed.lastScope) ? parsed.lastScope : DEFAULT_SEARCH_SCOPE,
			recent: Array.isArray(parsed.recent)
				? parsed.recent.filter(isRecentSearch).slice(0, MAX_RECENT_SEARCHES)
				: [],
		};
	} catch {
		return EMPTY_MEMORY;
	}
};

/**
 * Records a search: the scope becomes the last used one, and a non-empty query moves to the front
 * of the recent list (once per scope + query, newest first, at most five). Returns the new memory
 * even when it cannot be stored, so the current page still reflects it.
 */
export const rememberSearch = (
	scope: SearchScope,
	query: string,
	storage: Storage | null = getStorage(),
): SearchMemory => {
	const current = readSearchMemory(storage);
	const trimmed = query.trim();
	const recent = trimmed
		? [
				{ scope, query: trimmed },
				...current.recent.filter((entry) => !(entry.scope === scope && entry.query === trimmed)),
			].slice(0, MAX_RECENT_SEARCHES)
		: current.recent;
	const next: SearchMemory = { lastScope: scope, recent };

	try {
		storage?.setItem(SEARCH_MEMORY_KEY, JSON.stringify(next));
	} catch {
		// Quota or privacy mode: the search still runs, it just is not remembered.
	}

	return next;
};

import { useQuery } from '@tanstack/react-query';
import type { SearchCountsResource } from '@/api/generated/model';
import { getSearchCountsIndexQueryKey, searchCountsIndex } from '@/api/generated/search-counts/search-counts';
import { useAuth } from '@/hooks/useAuth';

/** Hits per scope, as each list reports its total. `null`: that list rejects the keyword. */
export type SearchCounts = Omit<SearchCountsResource, 'q'>;

/** Counts change only on import (dictionary) or when someone publishes (articles). */
const STALE_TIME_MS = 60_000;

/**
 * What Laravel's `TrimStrings` strips from both ends (`Str::trim`: ASCII whitespace, NUL and
 * `Str::INVISIBLE_CHARACTERS`). The client trims the same way, so the server's echo of `q` matches
 * a pasted query that ends in, say, a zero-width space.
 */
const SERVER_TRIMMED = String.raw`\t\n\v\f\r \0\u00A0\u00AD\u034F\u061C\u115F\u1160\u17B4\u17B5\u180E\u2000-\u200F\u202F\u205F\u2060-\u2065\u206A-\u206F\u2800\u3000\u3164\uFEFF\uFFA0\u{1D159}\u{1D173}-\u{1D17A}\u{E0020}`;
// The set holds lone combining characters (U+034F, U+180E) on purpose: each is stripped on its own.
// eslint-disable-next-line no-misleading-character-class
const SERVER_TRIM_PATTERN = new RegExp(`^[${SERVER_TRIMMED}]+|[${SERVER_TRIMMED}]+$`, 'gu');

export const trimLikeServer = (value: string) => value.replace(SERVER_TRIM_PATTERN, '');

/**
 * The article count depends on who asks (guest, owner, admin), so the viewer is part of the key:
 * signing in or out never shows the other viewer's cached numbers.
 */
export const searchCountsKeys = {
	counts: (keyword: string, viewerUuid: string | null) =>
		[...getSearchCountsIndexQueryKey({ q: keyword }), { viewer: viewerUuid }] as const,
} as const;

/**
 * Per-scope hit counts for the Header search. Pass the debounced query; nothing is fetched for
 * an empty one. `counts` is set only when the answer is for this exact keyword, so a row never
 * shows the previous query's number. Failures are not retried: the next keystroke asks again,
 * and the caller falls back to detection meanwhile.
 *
 * Nothing is fetched while a stored session restores: the request would already carry the token,
 * but `user` is still unknown, so the viewer's own count would be cached under the guest key.
 */
export const useSearchCounts = (query: string): { counts: SearchCounts | null } => {
	const { user, isLoading: isRestoringSession } = useAuth();
	const keyword = trimLikeServer(query);
	const viewerUuid = user?.uuid ?? null;

	const { data } = useQuery({
		queryKey: searchCountsKeys.counts(keyword, viewerUuid),
		queryFn: ({ signal }) => searchCountsIndex({ q: keyword }, undefined, signal),
		enabled: keyword !== '' && !isRestoringSession,
		staleTime: STALE_TIME_MS,
		retry: false,
	});

	if (!data || keyword === '' || data.q !== keyword) return { counts: null };

	const { q: _echoed, ...counts } = data;
	return { counts };
};

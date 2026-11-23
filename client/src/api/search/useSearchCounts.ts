import { useQuery } from '@tanstack/react-query';
import type { SearchCountsResource } from '@/api/generated/model';
import { getSearchCountsIndexQueryKey, searchCountsIndex } from '@/api/generated/search-counts/search-counts';
import { useAuth } from '@/hooks/useAuth';

/** Hits per scope, as each list reports its total. `null`: that list rejects the keyword. */
export type SearchCounts = Omit<SearchCountsResource, 'q'>;

/** Counts change only on import (dictionary) or when someone publishes (articles). */
const STALE_TIME_MS = 60_000;

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
 * an empty one. The keyword is in the query key, so the counts returned are always the ones asked
 * for this keyword. Failures are not retried: the next keystroke asks again, and the caller falls
 * back to detection meanwhile.
 *
 * Nothing is fetched while a stored session restores: the request would already carry the token,
 * but `user` is still unknown, so the viewer's own count would be cached under the guest key.
 */
export const useSearchCounts = (query: string): { counts: SearchCounts | null } => {
	const { user, isLoading: isRestoringSession } = useAuth();
	const keyword = query.trim();
	const viewerUuid = user?.uuid ?? null;

	const { data } = useQuery({
		queryKey: searchCountsKeys.counts(keyword, viewerUuid),
		queryFn: ({ signal }) => searchCountsIndex({ q: keyword }, undefined, signal),
		enabled: keyword !== '' && !isRestoringSession,
		staleTime: STALE_TIME_MS,
		retry: false,
	});

	if (!data || keyword === '') return { counts: null };

	const { q: _echoed, ...counts } = data;
	return { counts };
};

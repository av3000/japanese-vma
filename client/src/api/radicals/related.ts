import { getKanjiIndexQueryKey, kanjiIndex } from '@/api/generated/kanji/kanji';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import { KANJI_VIEWER_CATALOGUE_INCLUDE, type KanjiListFilters } from '@/api/kanjis/hooks/useInfiniteKanjis';
import { usePagedIndex } from '@/api/pagedIndex';

/**
 * The kanji built on one radical, read from the kanji index filtered by `radical_uuid` (#352)
 * rather than the radical payload's inline list, which carries every kanji at once (#548).
 */

export const RADICAL_KANJI_PAGE_SIZE = 25;

export const radicalKanjiFilters = (radicalUuid: string): KanjiListFilters => ({
	radical_uuid: radicalUuid,
	per_page: RADICAL_KANJI_PAGE_SIZE,
	include: KANJI_VIEWER_CATALOGUE_INCLUDE,
});

/** The kanji built on a radical, a numbered page (25) at a time, with viewer save state. */
export const useRadicalKanjiPages = (radicalUuid: string) =>
	usePagedIndex<KanjiListFilters, KanjiResource>({
		filters: radicalKanjiFilters(radicalUuid),
		queryKey: getKanjiIndexQueryKey,
		fetchPage: (params, signal) => kanjiIndex(params, undefined, signal),
		enabled: Boolean(radicalUuid),
	});

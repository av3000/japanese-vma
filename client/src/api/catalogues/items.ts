import { useMutation, useQueryClient } from '@tanstack/react-query';
import { catalogueRemoveItem, getCatalogueShowQueryKey } from '@/api/generated/catalogue/catalogue';
import { getKanjiIndexQueryKey, kanjiIndex } from '@/api/generated/kanji/kanji';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import type { RadicalResource } from '@/api/generated/model/radicalResource';
import type { SentenceResource } from '@/api/generated/model/sentenceResource';
import type { WordResource } from '@/api/generated/model/wordResource';
import { getRadicalIndexQueryKey, radicalIndex } from '@/api/generated/radical/radical';
import { getSentenceIndexQueryKey, sentenceIndex } from '@/api/generated/sentence/sentence';
import { getWordIndexQueryKey, wordIndex } from '@/api/generated/word/word';
import {
	KANJI_VIEWER_CATALOGUE_INCLUDE,
	getInfiniteKanjisQueryKey,
	type KanjiListFilters,
} from '@/api/kanjis/hooks/useInfiniteKanjis';
import { usePagedIndex } from '@/api/pagedIndex';
import { getInfiniteRadicalsQueryKey, type RadicalListFilters } from '@/api/radicals/hooks/useInfiniteRadicals';
import { getInfiniteSentencesQueryKey, type SentenceListFilters } from '@/api/sentences/hooks/useInfiniteSentences';
import {
	WORD_VIEWER_CATALOGUE_INCLUDE,
	getInfiniteWordsQueryKey,
	type WordListFilters,
} from '@/api/words/hooks/useInfiniteWords';
import type { CatalogueFamily } from '@/shared/constants/catalogues';

/**
 * A catalogue's items, read from the dictionary indexes filtered by `catalogue_uuid` (#347), as
 * article attachments are read by `article_uuid` (#268). The indexes page, type and carry viewer
 * save state, which the catalogue detail payload's untyped `items` does not. Article catalogues
 * still read that payload. The filters live here so the lists and the removal that refreshes them
 * use the same query keys: removal invalidates the filters, which reaches every numbered page.
 */

export const CATALOGUE_ITEMS_PAGE_SIZE = 25;

export const catalogueKanjiFilters = (catalogueUuid: string): KanjiListFilters => ({
	catalogue_uuid: catalogueUuid,
	per_page: CATALOGUE_ITEMS_PAGE_SIZE,
	include: KANJI_VIEWER_CATALOGUE_INCLUDE,
});

export const catalogueWordFilters = (catalogueUuid: string): WordListFilters => ({
	catalogue_uuid: catalogueUuid,
	per_page: CATALOGUE_ITEMS_PAGE_SIZE,
	include: WORD_VIEWER_CATALOGUE_INCLUDE,
});

export const catalogueRadicalFilters = (catalogueUuid: string): RadicalListFilters => ({
	catalogue_uuid: catalogueUuid,
	per_page: CATALOGUE_ITEMS_PAGE_SIZE,
});

export const catalogueSentenceFilters = (catalogueUuid: string): SentenceListFilters => ({
	catalogue_uuid: catalogueUuid,
	per_page: CATALOGUE_ITEMS_PAGE_SIZE,
});

/** The items query of a dictionary family; `null` for article catalogues, which use the payload. */
export const catalogueItemsQueryKey = (family: CatalogueFamily, catalogueUuid: string) => {
	switch (family) {
		case 'kanji':
			return getInfiniteKanjisQueryKey(catalogueKanjiFilters(catalogueUuid));
		case 'words':
			return getInfiniteWordsQueryKey(catalogueWordFilters(catalogueUuid));
		case 'radicals':
			return getInfiniteRadicalsQueryKey(catalogueRadicalFilters(catalogueUuid));
		case 'sentences':
			return getInfiniteSentencesQueryKey(catalogueSentenceFilters(catalogueUuid));
		case 'articles':
			return null;
	}
};

/** Drops a removed item from the cached detail payload and lowers its count. */
export const withoutCatalogueItem = (
	catalogue: CatalogueDetailResource | undefined,
	itemId: number,
): CatalogueDetailResource | undefined => {
	if (!catalogue) return catalogue;

	const items = (catalogue.items as unknown as Array<{ id: number }>).filter((item) => item.id !== itemId);
	const removed = items.length < catalogue.items.length;

	return {
		...catalogue,
		items: items as unknown as CatalogueDetailResource['items'],
		items_count: removed ? Math.max(0, Number(catalogue.items_count) - 1) : Number(catalogue.items_count),
	};
};

/**
 * Removes one item through the generated v1 client, then updates the cached detail (items and
 * count) and refetches the family's item list so its pages and total stay right.
 */
export const useRemoveCatalogueItem = (catalogueUuid: string, family: CatalogueFamily) => {
	const queryClient = useQueryClient();

	return useMutation<unknown, unknown, number>({
		mutationFn: (itemId: number) => catalogueRemoveItem(catalogueUuid, itemId),
		onSuccess: async (_result, itemId) => {
			queryClient.setQueryData<CatalogueDetailResource>(getCatalogueShowQueryKey(catalogueUuid), (old) =>
				withoutCatalogueItem(old, itemId),
			);

			const itemsKey = catalogueItemsQueryKey(family, catalogueUuid);

			if (itemsKey) await queryClient.invalidateQueries({ queryKey: itemsKey });
		},
	});
};

/** A catalogue's kanji, a numbered page (25) at a time or all at once (#522). */
export const useCatalogueKanjiPages = (catalogueUuid: string) =>
	usePagedIndex<KanjiListFilters, KanjiResource>({
		filters: catalogueKanjiFilters(catalogueUuid),
		queryKey: getKanjiIndexQueryKey,
		fetchPage: (params, signal) => kanjiIndex(params, undefined, signal),
	});

export const useCatalogueWordPages = (catalogueUuid: string) =>
	usePagedIndex<WordListFilters, WordResource>({
		filters: catalogueWordFilters(catalogueUuid),
		queryKey: getWordIndexQueryKey,
		fetchPage: (params, signal) => wordIndex(params, undefined, signal),
	});

export const useCatalogueRadicalPages = (catalogueUuid: string) =>
	usePagedIndex<RadicalListFilters, RadicalResource>({
		filters: catalogueRadicalFilters(catalogueUuid),
		queryKey: getRadicalIndexQueryKey,
		fetchPage: (params, signal) => radicalIndex(params, undefined, signal),
	});

export const useCatalogueSentencePages = (catalogueUuid: string) =>
	usePagedIndex<SentenceListFilters, SentenceResource>({
		filters: catalogueSentenceFilters(catalogueUuid),
		queryKey: getSentenceIndexQueryKey,
		fetchPage: (params, signal) => sentenceIndex(params, undefined, signal),
	});

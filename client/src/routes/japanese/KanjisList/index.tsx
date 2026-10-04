import { useCallback, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import { KanjiIndexJlpt } from '@/api/generated/model/kanjiIndexJlpt';
import type { KanjiListResource } from '@/api/generated/model/kanjiListResource';
import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import {
	KANJI_VIEWER_CATALOGUE_INCLUDE,
	applyKanjiViewerCatalogueState,
	getInfiniteKanjisQueryKey,
	type KanjiListFilters,
	useInfiniteKanjis,
} from '@/api/kanjis/hooks/useInfiniteKanjis';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import {
	DICTIONARY_PER_PAGE,
	DictionaryListPage,
	useKeywordSearch,
} from '@/components/features/japanese/dictionaryList';
import { useAuth } from '@/hooks/useAuth';
import KanjiFilters from './KanjiFilters';
import type { KanjiSearchFilters } from './KanjiFilters';

const VALID_JLPT_FILTERS = new Set<string>(Object.values(KanjiIndexJlpt));

const getJlptFilter = (value: string | undefined) => {
	if (!value || !VALID_JLPT_FILTERS.has(value)) {
		return undefined;
	}

	return value as NonNullable<KanjiListFilters['jlpt']>;
};

const getKanjiListFilters = (searchParams: URLSearchParams): KanjiListFilters => {
	const keyword = searchParams.get('keyword')?.trim();
	const jlpt = getJlptFilter(searchParams.get('jlpt')?.trim());

	return {
		per_page: DICTIONARY_PER_PAGE,
		include: KANJI_VIEWER_CATALOGUE_INCLUDE,
		...(keyword ? { keyword } : {}),
		...(jlpt ? { jlpt } : {}),
	};
};

const KanjisList = () => {
	const queryClient = useQueryClient();
	const { isAuthenticated } = useAuth();
	const { searchParams, keyword, applySearch } = useKeywordSearch();
	const filters = useMemo(() => getKanjiListFilters(searchParams), [searchParams]);
	const jlpt = filters.jlpt ?? '';

	const { kanjis, ...query } = useInfiniteKanjis({ filters });

	const handleSearch = useCallback(
		(next: KanjiSearchFilters) => applySearch({ keyword: next.keyword, jlpt: next.jlpt }),
		[applySearch],
	);

	// Stable across renders, so the table keeps its columns and untouched rows do not re-render.
	const handleKanjiBookmarkStateChange = useCallback(
		(kanjiId: number, state: ViewerCatalogueStateResource) => {
			queryClient.setQueryData<InfiniteData<KanjiListResource>>(getInfiniteKanjisQueryKey(filters), (data) =>
				applyKanjiViewerCatalogueState(data, kanjiId, state),
			);
		},
		[queryClient, filters],
	);

	return (
		<DictionaryListPage
			title="Kanji"
			noun="kanji"
			keyword={keyword}
			extraMeta={jlpt !== '' ? [`JLPT: ${jlpt === '-' ? 'Uncommon' : `N${jlpt}`}`] : []}
			hasOtherFilters={jlpt !== ''}
			itemCount={kanjis.length}
			query={query}
			filters={
				<KanjiFilters
					key={`${keyword}|${jlpt}`}
					defaultKeyword={keyword}
					defaultJlpt={jlpt}
					onSearch={handleSearch}
				/>
			}
		>
			{({ loading, empty }) => (
				<KanjiTable
					kanjis={kanjis}
					loading={loading}
					showSave={isAuthenticated}
					empty={empty}
					onBookmarkStateChange={handleKanjiBookmarkStateChange}
				/>
			)}
		</DictionaryListPage>
	);
};

export default KanjisList;

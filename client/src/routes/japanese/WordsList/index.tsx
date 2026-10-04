import React from 'react';
import { useQueryClient } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import type { WordListResource } from '@/api/generated/model/wordListResource';
import {
	WORD_VIEWER_CATALOGUE_INCLUDE,
	applyWordViewerCatalogueState,
	getInfiniteWordsQueryKey,
	useInfiniteWords,
} from '@/api/words/hooks/useInfiniteWords';
import type { WordListFilters } from '@/api/words/hooks/useInfiniteWords';
import {
	DICTIONARY_PER_PAGE,
	DictionaryListPage,
	KeywordFilters,
	useKeywordSearch,
} from '@/components/features/japanese/dictionaryList';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { useAuth } from '@/hooks/useAuth';

const getWordListFilters = (keyword: string): WordListFilters => ({
	per_page: DICTIONARY_PER_PAGE,
	include: WORD_VIEWER_CATALOGUE_INCLUDE,
	...(keyword ? { keyword } : {}),
});

const WordsList: React.FC = () => {
	const queryClient = useQueryClient();
	const { isAuthenticated } = useAuth();
	const { keyword, applySearch } = useKeywordSearch();
	const queryFilters = getWordListFilters(keyword);
	const { words, ...query } = useInfiniteWords({ filters: queryFilters });

	const handleSearch = (nextKeyword: string) => applySearch({ keyword: nextKeyword });

	const handleWordBookmarkStateChange = (wordId: number, state: ViewerCatalogueStateResource) => {
		queryClient.setQueryData<InfiniteData<WordListResource>>(getInfiniteWordsQueryKey(queryFilters), (data) =>
			applyWordViewerCatalogueState(data, wordId, state),
		);
	};

	return (
		<DictionaryListPage
			title="Words"
			noun="words"
			keyword={keyword}
			itemCount={words.length}
			query={query}
			filters={
				<KeywordFilters
					key={keyword}
					label="Word filters"
					searchLabel="Search words by keyword"
					placeholder="Word, reading or meaning"
					defaultKeyword={keyword}
					onSearch={handleSearch}
				/>
			}
		>
			{({ loading, empty }) => (
				<WordTable
					words={words}
					loading={loading}
					showSave={isAuthenticated}
					empty={empty}
					onBookmarkStateChange={handleWordBookmarkStateChange}
				/>
			)}
		</DictionaryListPage>
	);
};

export default WordsList;

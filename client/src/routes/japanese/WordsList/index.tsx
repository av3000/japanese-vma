import React from 'react';
import { useSearchParams } from 'react-router-dom';
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
	LoadMore,
	emptySearch,
	showingCount,
} from '@/components/features/japanese/dictionaryList';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { Alert } from '@/components/shared/Alert';
import { useAuth } from '@/hooks/useAuth';

const getWordListFilters = (searchParams: URLSearchParams): WordListFilters => {
	const keyword = searchParams.get('keyword')?.trim();

	return {
		per_page: DICTIONARY_PER_PAGE,
		include: WORD_VIEWER_CATALOGUE_INCLUDE,
		...(keyword ? { keyword } : {}),
	};
};

const WordsList: React.FC = () => {
	const queryClient = useQueryClient();
	const { isAuthenticated } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const queryFilters = getWordListFilters(searchParams);
	const keyword = queryFilters.keyword ?? '';
	const { words, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError } =
		useInfiniteWords({
			filters: queryFilters,
		});

	const handleSearch = (nextKeyword: string) => {
		const nextParams = new URLSearchParams();

		if (nextKeyword !== '') {
			nextParams.set('keyword', nextKeyword);
		}

		setSearchParams(nextParams);
	};

	const handleWordBookmarkStateChange = (wordId: number, state: ViewerCatalogueStateResource) => {
		queryClient.setQueryData<InfiniteData<WordListResource>>(getInfiniteWordsQueryKey(queryFilters), (data) =>
			applyWordViewerCatalogueState(data, wordId, state),
		);
	};

	const isInitialLoading = isPending && words.length === 0;
	const meta = isInitialLoading
		? undefined
		: [showingCount(words.length, total), keyword !== '' && `keyword: ${keyword}`].filter(Boolean).join(' · ');

	const filterBar = (
		<KeywordFilters
			key={keyword}
			label="Word filters"
			searchLabel="Search words by keyword"
			placeholder="Word, reading or meaning"
			defaultKeyword={keyword}
			onSearch={handleSearch}
		/>
	);

	if (isError) {
		const message = error instanceof Error ? error.message : 'Unable to load words.';

		return (
			<DictionaryListPage title="Words" filters={filterBar}>
				<Alert tone="danger">Error: {message}</Alert>
			</DictionaryListPage>
		);
	}

	return (
		<DictionaryListPage title="Words" meta={meta} filters={filterBar}>
			<WordTable
				words={words}
				loading={isInitialLoading}
				showSave={isAuthenticated}
				empty={emptySearch('words', keyword)}
				onBookmarkStateChange={handleWordBookmarkStateChange}
			/>
			{isInitialLoading || words.length === 0 ? null : (
				<LoadMore
					hasNextPage={hasNextPage}
					isFetchingNextPage={isFetchingNextPage}
					onLoadMore={() => void fetchNextPage()}
				/>
			)}
		</DictionaryListPage>
	);
};

export default WordsList;

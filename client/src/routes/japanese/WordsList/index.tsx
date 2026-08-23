import React from 'react';
import { useQueryClient } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import {
	WORD_VIEWER_CATALOGUE_INCLUDE,
	applyWordViewerCatalogueState,
	getInfiniteWordsQueryKey,
	useInfiniteWords,
} from '@/api/words/hooks/useInfiniteWords';
import type { WordListFilters, WordListResponse, WordViewerCatalogueState } from '@/api/words/hooks/useInfiniteWords';
import Spinner from '@/assets/images/spinner.gif';
import WordItem from '@/components/features/japanese/word/WordItem';
import { Button } from '@/components/shared/Button';
import SearchBarWords from './SearchBarWords';
import type { WordSearchFilters } from './SearchBarWords';

const DEFAULT_PER_PAGE = 10;

const getWordListFilters = (searchParams: URLSearchParams): WordListFilters => {
	const keyword = searchParams.get('keyword')?.trim();

	return {
		per_page: DEFAULT_PER_PAGE,
		include: WORD_VIEWER_CATALOGUE_INCLUDE,
		...(keyword ? { keyword } : {}),
	};
};

const WordsList: React.FC = () => {
	const queryClient = useQueryClient();
	const [searchParams, setSearchParams] = useSearchParams();
	const queryFilters = getWordListFilters(searchParams);
	const keyword = queryFilters.keyword ?? '';
	const { words, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError } =
		useInfiniteWords({
			filters: queryFilters,
		});

	const handleApplyFilters = (newFilters: WordSearchFilters) => {
		const nextParams = new URLSearchParams();

		if (newFilters.keyword !== '') {
			nextParams.set('keyword', newFilters.keyword);
		}

		setSearchParams(nextParams);
	};

	const handleWordBookmarkStateChange = (wordId: number, state: WordViewerCatalogueState) => {
		queryClient.setQueryData<InfiniteData<WordListResponse>>(getInfiniteWordsQueryKey(queryFilters), (data) =>
			applyWordViewerCatalogueState(data, wordId, state),
		);
	};

	const searchHeading =
		keyword ? `Results for: ${keyword}` : '';

	if (isPending && words.length === 0) {
		return (
			<div className="container text-center">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (isError) {
		const message = error instanceof Error ? error.message : 'Unable to load words.';

		return <div className="container mt-5 text-danger">Error: {message}</div>;
	}

	return (
		<div className="container mt-5">
			<div className="row justify-content-center">
				<SearchBarWords defaultKeyword={keyword} onSearch={handleApplyFilters} />
			</div>
			<div className="container mt-5">
				<div className="row justify-content-center">
					{searchHeading && <h4>{searchHeading}</h4>}
					&nbsp;
					<h4>
						Showing {words.length} of {total}
					</h4>
				</div>
				<div className="row">
					<div className="col-lg-8 col-md-10 mx-auto">
						{words.length === 0 ? (
							<p>No words found.</p>
						) : (
							words.map((word) => (
								<WordItem
									key={word.uuid}
									entityId={word.id}
									detailIdentifier={word.uuid}
									word={word.word}
									furigana={word.furigana}
									word_type={word.word_type}
									meaning={word.meaning}
									jlpt={word.jlpt ?? ''}
									isSaved={word.viewer_catalogue_state?.is_saved ?? false}
									isKnown={word.viewer_catalogue_state?.is_known ?? false}
									onBookmarkStateChange={(state) =>
										handleWordBookmarkStateChange(word.id, {
											is_saved: state.isBookmarked,
											is_known: state.isKnown,
										})
									}
								/>
							))
						)}
					</div>
				</div>
			</div>
			<div className="row justify-content-center">
				{isFetchingNextPage ? (
					<img src={Spinner} alt="Loading more..." style={{ height: '40px' }} />
				) : hasNextPage ? (
					<Button variant="secondary-outline" className="w-50" onClick={() => fetchNextPage()}>
						Load More
					</Button>
				) : (
					<span className="text-muted">No more results</span>
				)}
			</div>
		</div>
	);
};

export default WordsList;

import React from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import {
	WORD_VIEWER_CATALOGUE_INCLUDE,
	applyWordViewerCatalogueState,
	getInfiniteWordsQueryKey,
	useInfiniteWords,
} from '@/api/words/hooks/useInfiniteWords';
import type { WordListFilters, WordListResponse, WordViewerCatalogueState } from '@/api/words/hooks/useInfiniteWords';
import Spinner from '@/assets/images/spinner.gif';
import WordItem from '@/components/features/japanese/word/WordItem';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import styles from '../japaneseListPage.module.css';
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

	const searchHeading = keyword ? `Results for: ${keyword}` : '';

	if (isPending && words.length === 0) {
		return <PageLoading family="list" />;
	}

	if (isError) {
		const message = error instanceof Error ? error.message : 'Unable to load words.';

		return (
			<Container className={styles.page}>
				<Alert tone="danger">Error: {message}</Alert>
			</Container>
		);
	}

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<SearchBarWords defaultKeyword={keyword} onSearch={handleApplyFilters} />
				<Stack as="section" gap="md" className={styles.results}>
					<Cluster justify="center" align="baseline" gap="md">
						{searchHeading && <h4 className={styles.heading}>{searchHeading}</h4>}
						<h4 className={styles.heading}>
							Showing {words.length} of {total}
						</h4>
					</Cluster>
					{words.length === 0 ? (
						<p>No words found.</p>
					) : (
						<ul className={styles.list}>
							{words.map((word) => (
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
							))}
						</ul>
					)}
				</Stack>
				<Cluster justify="center">
					{isFetchingNextPage ? (
						<img src={Spinner} alt="Loading more..." className={styles.loadingMore} />
					) : hasNextPage ? (
						<Button variant="secondary-outline" className={styles.loadMore} onClick={() => fetchNextPage()}>
							Load More
						</Button>
					) : (
						<span className={styles.muted}>No more results</span>
					)}
				</Cluster>
			</Stack>
		</Container>
	);
};

export default WordsList;

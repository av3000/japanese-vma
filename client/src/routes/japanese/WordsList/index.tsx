import React, { useMemo, useState } from 'react';
import { useInfiniteWords } from '@/api/words/hooks/useInfiniteWords';
import type { WordListFilters } from '@/api/words/hooks/useInfiniteWords';
import Spinner from '@/assets/images/spinner.gif';
import WordItem from '@/components/features/japanese/word/WordItem';
import { Button } from '@/components/shared/Button';
import SearchBarWords from './SearchBarWords';
import type { WordSearchFilters } from './SearchBarWords';

const DEFAULT_PER_PAGE = 10;

const mapSearchFiltersToWordParams = (filters: WordSearchFilters | Record<string, never>): WordListFilters => ({
	keyword: typeof filters.keyword === 'string' && filters.keyword.trim() ? filters.keyword.trim() : undefined,
	per_page: DEFAULT_PER_PAGE,
});

const WordsList: React.FC = () => {
	const [filters, setFilters] = useState<WordSearchFilters | Record<string, never>>({});
	const queryFilters = useMemo(() => mapSearchFiltersToWordParams(filters), [filters]);
	const { words, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError } =
		useInfiniteWords({
			filters: queryFilters,
		});

	const handleApplyFilters = (newFilters: WordSearchFilters) => {
		setFilters(newFilters);
	};

	const searchHeading =
		typeof filters.keyword === 'string' && filters.keyword ? `Results for: ${filters.keyword}` : '';

	const handleAddToList = () => undefined;

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
				<SearchBarWords fetchQuery={handleApplyFilters} />
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
									id={word.id}
									word={word.word}
									furigana={word.furigana}
									word_type={word.word_type}
									meaning={word.meaning}
									jlpt={word.jlpt ?? ''}
									addToList={handleAddToList}
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

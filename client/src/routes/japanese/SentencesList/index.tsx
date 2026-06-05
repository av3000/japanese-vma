import { useSearchParams } from 'react-router-dom';
import { useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import Spinner from '@/assets/images/spinner.gif';
import SentenceItem from '@/components/features/japanese/sentence/SentenceItem';
import SearchBarSentences from './SearchBarSentences';

const getSentenceListFilters = (searchParams: URLSearchParams) => {
	const keyword = searchParams.get('keyword')?.trim();

	return keyword ? { keyword } : {};
};

const SentencesList = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const filters = getSentenceListFilters(searchParams);
	const keyword = filters.keyword ?? '';

	const {
		sentences,
		total,
		isLoading,
		isFetchingNextPage,
		hasNextPage,
		fetchNextPage,
		error,
	} = useInfiniteSentences({ filters });

	const handleSearch = (nextKeyword: string) => {
		const nextParams = new URLSearchParams();

		if (nextKeyword !== '') {
			nextParams.set('keyword', nextKeyword);
		}

		setSearchParams(nextParams);
	};

	if (isLoading) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			</div>
		);
	}

	if (error) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<p>Sentences could not be loaded.</p>
				</div>
			</div>
		);
	}

	return (
		<div className="container mt-5">
			<div className="row justify-content-center">
				<SearchBarSentences defaultKeyword={keyword} onSearch={handleSearch} />
			</div>

			<div className="container mt-5">
				<div className="row justify-content-center">
					{keyword ? <h4>keyword: {keyword}</h4> : null}
					&nbsp;
					<h4>Results total: '{total}'</h4>
				</div>
				<div className="row">
					<div className="col-lg-8 col-md-10 mx-auto">
						{sentences.map((sentence) => (
							<SentenceItem
								key={sentence.uuid}
								id={sentence.id}
								tatoeba_entry={sentence.tatoeba_entry ?? undefined}
								userId={sentence.user_id ?? undefined}
								sentence={sentence.content}
								addToList={() => undefined}
							/>
						))}
					</div>
				</div>
			</div>

			<div className="row justify-content-center">
				{hasNextPage ? (
					<button
						className="btn btn-outline-primary brand-button w-50"
						onClick={() => void fetchNextPage()}
						disabled={isFetchingNextPage}
					>
						{isFetchingNextPage ? 'Loading...' : 'Load More'}
					</button>
				) : (
					'no more results...'
				)}
			</div>
		</div>
	);
};

export default SentencesList;

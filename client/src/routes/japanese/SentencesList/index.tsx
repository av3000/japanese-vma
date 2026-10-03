import { useSearchParams } from 'react-router-dom';
import { type SentenceListFilters, useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import {
	DICTIONARY_PER_PAGE,
	DictionaryListPage,
	KeywordFilters,
	LoadMore,
	emptySearch,
	showingCount,
} from '@/components/features/japanese/dictionaryList';
import { SentenceTable } from '@/components/features/japanese/sentence/SentenceTable';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { useAuth } from '@/hooks/useAuth';

const getSentenceListFilters = (searchParams: URLSearchParams): SentenceListFilters => {
	const keyword = searchParams.get('keyword')?.trim();

	return { per_page: DICTIONARY_PER_PAGE, ...(keyword ? { keyword } : {}) };
};

const SentencesList = () => {
	const { isAuthenticated } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const filters = getSentenceListFilters(searchParams);
	const keyword = filters.keyword ?? '';

	const { sentences, total, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage, error } = useInfiniteSentences(
		{
			filters,
		},
	);

	const handleSearch = (nextKeyword: string) => {
		const nextParams = new URLSearchParams();

		if (nextKeyword !== '') {
			nextParams.set('keyword', nextKeyword);
		}

		setSearchParams(nextParams);
	};

	const createAction = isAuthenticated ? (
		<Button to="/sentences/new" variant="primary">
			Create sentence
		</Button>
	) : undefined;

	const meta = isLoading
		? undefined
		: [showingCount(sentences.length, total), keyword !== '' && `keyword: ${keyword}`].filter(Boolean).join(' · ');

	const filterBar = (
		<KeywordFilters
			key={keyword}
			label="Sentence filters"
			searchLabel="Search sentences by keyword"
			placeholder="Japanese text"
			defaultKeyword={keyword}
			onSearch={handleSearch}
		/>
	);

	if (error) {
		return (
			<DictionaryListPage title="Sentences" action={createAction} filters={filterBar}>
				<Alert tone="danger">Sentences could not be loaded.</Alert>
			</DictionaryListPage>
		);
	}

	return (
		<DictionaryListPage title="Sentences" meta={meta} action={createAction} filters={filterBar}>
			<SentenceTable sentences={sentences} loading={isLoading} empty={emptySearch('sentences', keyword)} />
			{isLoading || sentences.length === 0 ? null : (
				<LoadMore
					hasNextPage={hasNextPage}
					isFetchingNextPage={isFetchingNextPage}
					onLoadMore={() => void fetchNextPage()}
				/>
			)}
		</DictionaryListPage>
	);
};

export default SentencesList;

import { type SentenceListFilters, useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import {
	DICTIONARY_PER_PAGE,
	DictionaryListPage,
	KeywordFilters,
	useKeywordSearch,
} from '@/components/features/japanese/dictionaryList';
import { SentenceTable } from '@/components/features/japanese/sentence/SentenceTable';
import { Button } from '@/components/shared/Button';
import { useAuth } from '@/hooks/useAuth';

const getSentenceListFilters = (keyword: string): SentenceListFilters => ({
	per_page: DICTIONARY_PER_PAGE,
	...(keyword ? { keyword } : {}),
});

const SentencesList = () => {
	const { isAuthenticated } = useAuth();
	const { keyword, applySearch } = useKeywordSearch();
	const { sentences, ...query } = useInfiniteSentences({ filters: getSentenceListFilters(keyword) });

	const handleSearch = (nextKeyword: string) => applySearch({ keyword: nextKeyword });

	const createAction = isAuthenticated ? (
		<Button to="/sentences/new" variant="primary">
			Create sentence
		</Button>
	) : undefined;

	return (
		<DictionaryListPage
			title="Sentences"
			noun="sentences"
			keyword={keyword}
			itemCount={sentences.length}
			query={query}
			action={createAction}
			filters={
				<KeywordFilters
					key={keyword}
					label="Sentence filters"
					searchLabel="Search sentences by keyword"
					placeholder="Japanese text"
					defaultKeyword={keyword}
					onSearch={handleSearch}
				/>
			}
		>
			{({ loading, empty }) => <SentenceTable sentences={sentences} loading={loading} empty={empty} />}
		</DictionaryListPage>
	);
};

export default SentencesList;

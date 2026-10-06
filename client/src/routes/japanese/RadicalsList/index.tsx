import { type RadicalListFilters, useInfiniteRadicals } from '@/api/radicals/hooks/useInfiniteRadicals';
import {
	DICTIONARY_PER_PAGE,
	DictionaryListPage,
	KeywordFilters,
	useKeywordSearch,
} from '@/components/features/japanese/dictionaryList';
import { RadicalTable } from '@/components/features/japanese/radical/RadicalTable';

const getRadicalListFilters = (keyword: string): RadicalListFilters => ({
	per_page: DICTIONARY_PER_PAGE,
	...(keyword ? { keyword } : {}),
});

const RadicalsList = () => {
	const { keyword, applySearch } = useKeywordSearch();
	const { radicals, ...query } = useInfiniteRadicals({ filters: getRadicalListFilters(keyword) });

	const handleSearch = (nextKeyword: string) => applySearch({ keyword: nextKeyword });

	return (
		<DictionaryListPage
			title="Radicals"
			noun="radicals"
			keyword={keyword}
			itemCount={radicals.length}
			query={query}
			filters={
				<KeywordFilters
					key={keyword}
					label="Radical filters"
					searchLabel="Search radicals by keyword"
					placeholder="Radical, meaning or reading"
					defaultKeyword={keyword}
					onSearch={handleSearch}
				/>
			}
		>
			{({ loading, empty }) => <RadicalTable radicals={radicals} loading={loading} empty={empty} />}
		</DictionaryListPage>
	);
};

export default RadicalsList;

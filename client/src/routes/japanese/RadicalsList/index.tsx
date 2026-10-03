import { useSearchParams } from 'react-router-dom';
import { type RadicalListFilters, useInfiniteRadicals } from '@/api/radicals/hooks/useInfiniteRadicals';
import {
	DICTIONARY_PER_PAGE,
	DictionaryListPage,
	KeywordFilters,
	LoadMore,
	emptySearch,
	showingCount,
} from '@/components/features/japanese/dictionaryList';
import { RadicalTable } from '@/components/features/japanese/radical/RadicalTable';
import { Alert } from '@/components/shared/Alert';

const RadicalsList = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const keyword = searchParams.get('keyword')?.trim() ?? '';
	const filters: RadicalListFilters = { per_page: DICTIONARY_PER_PAGE, ...(keyword ? { keyword } : {}) };
	const { radicals, total, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage, isError } = useInfiniteRadicals(
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

	const meta = isLoading
		? undefined
		: [showingCount(radicals.length, total), keyword !== '' && `keyword: ${keyword}`].filter(Boolean).join(' · ');

	const filterBar = (
		<KeywordFilters
			key={keyword}
			label="Radical filters"
			searchLabel="Search radicals by keyword"
			placeholder="Radical, meaning or reading"
			defaultKeyword={keyword}
			onSearch={handleSearch}
		/>
	);

	if (isError) {
		return (
			<DictionaryListPage title="Radicals" filters={filterBar}>
				<Alert tone="danger">Unable to load radicals.</Alert>
			</DictionaryListPage>
		);
	}

	return (
		<DictionaryListPage title="Radicals" meta={meta} filters={filterBar}>
			<RadicalTable radicals={radicals} loading={isLoading} empty={emptySearch('radicals', keyword)} />
			{isLoading || radicals.length === 0 ? null : (
				<LoadMore
					hasNextPage={hasNextPage}
					isFetchingNextPage={isFetchingNextPage}
					onLoadMore={() => void fetchNextPage()}
				/>
			)}
		</DictionaryListPage>
	);
};

export default RadicalsList;

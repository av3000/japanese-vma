import { useSearchParams } from 'react-router-dom';
import {
	type RadicalListFilters,
	useInfiniteRadicals,
} from '@/api/radicals/hooks/useInfiniteRadicals';
import RadicalItem from '@/components/features/japanese/radical/RadicalItem';
import { PageLoading } from '@/components/shared/PageLoading';
import SearchBarRadicals from './SearchBarRadicals';

const DEFAULT_PER_PAGE = 10;

const RadicalsList = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const keyword = searchParams.get('keyword')?.trim() ?? '';
	const filters: RadicalListFilters = { per_page: DEFAULT_PER_PAGE, ...(keyword ? { keyword } : {}) };
	const { radicals, total, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage, isError } =
		useInfiniteRadicals({ filters });

	const handleSearch = (nextKeyword: string) => {
		const nextParams = new URLSearchParams();

		if (nextKeyword !== '') {
			nextParams.set('keyword', nextKeyword);
		}

		setSearchParams(nextParams);
	};

	if (isLoading) {
		return <PageLoading family="list" />;
	}

	const searchTotal = `Results total: '${total}'`;

	return (
		<div className="container mt-5">
			<div className="row justify-content-center">
				<SearchBarRadicals defaultKeyword={keyword} onSearch={handleSearch} />
			</div>
			<div className="container mt-5">
				<div className="row justify-content-center">
					<h4>{searchTotal}</h4>
				</div>
				{isError && (
					<div className="row justify-content-center">
						<p>Unable to load radicals.</p>
					</div>
				)}
				<div className="row">
					<div className="col-lg-8 col-md-10 mx-auto">
						{radicals.map((radical) => (
							<RadicalItem
								key={radical.uuid}
								entityId={radical.id}
								detailIdentifier={radical.uuid}
								radical={radical.radical}
								strokes={radical.strokes}
								meaning={radical.meaning}
								hiragana={radical.hiragana}
							/>
						))}
						{radicals.length === 0 && !isError && <p>No radicals found.</p>}
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

export default RadicalsList;

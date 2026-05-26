import { useState } from 'react';
import {
	type RadicalListFilters,
	useInfiniteRadicals,
} from '@/api/radicals/hooks/useInfiniteRadicals';
import Spinner from '@/assets/images/spinner.gif';
import RadicalItem from '@/components/features/japanese/radical/RadicalItem';
import SearchBarRadicals from './SearchBarRadicals';

const RadicalsList = () => {
	const [filters, setFilters] = useState<RadicalListFilters>({});
	const { radicals, total, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage, isError } =
		useInfiniteRadicals({ filters });

	if (isLoading) {
		return (
			<div className="container text-center">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	const searchTotal = `Results total: '${total}'`;

	return (
		<div className="container mt-5">
			<div className="row justify-content-center">
				<SearchBarRadicals onSearch={setFilters} />
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
							<RadicalItem key={radical.id} {...radical} />
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

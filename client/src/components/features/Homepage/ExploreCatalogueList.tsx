import React from 'react';
import { Link } from 'react-router-dom';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useCatalogueIndex } from '@/api/generated/catalogue/catalogue';
import Spinner from '@/assets/images/spinner.gif';
import { CatalogueCard } from '@/components/features/catalogues/CatalogueCard';

export const HOMEPAGE_CATALOGUE_FILTERS: FetchCataloguesFilters = {
	per_page: 3,
	public_only: true,
	custom_only: true,
	include_stats_counts: true,
	include_hashtags: true,
};

// TODO: Explore catalogues shouldnt include 'default' catalogues.
// TODO: Add 'isDefaultList' property for catalogue persistence model, that would also make them immutable
const ExploreCatalogueList: React.FC = () => {
	const { data, isPending } = useCatalogueIndex(HOMEPAGE_CATALOGUE_FILTERS);

	const lists = data?.items ?? [];
	const totalLists = data?.pagination.total ?? 0;

	if (isPending) {
		return (
			<div className="d-flex justify-content-center w-100">
				<img src={Spinner} alt="spinner loading" />
			</div>
		);
	}

	return (
		<>
			<div className="d-flex justify-content-between align-items-center w-100 my-3">
				<h3>Latest Catalogues ({totalLists || 0})</h3>
				<div>
					<Link to="/catalogues" className="homepage-section-title">
						Read All Catalogues
					</Link>
				</div>
			</div>
			<div className="row">
				{lists.map((catalogue) => (
					<CatalogueCard key={catalogue.uuid} catalogue={catalogue} />
				))}
			</div>
		</>
	);
};

export default ExploreCatalogueList;

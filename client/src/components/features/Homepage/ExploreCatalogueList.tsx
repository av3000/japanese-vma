import React from 'react';
import { Link } from 'react-router-dom';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useCatalogueIndex } from '@/api/generated/catalogue/catalogue';
import { CatalogueCard } from '@/components/features/catalogues/CatalogueCard/CatalogueCard';
import { CatalogueCardSkeleton } from '@/components/features/catalogues/CatalogueCard/CatalogueCardSkeleton';

const HOMEPAGE_CATALOGUE_SKELETON_COUNT = 4;

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
	const { data, error, isPending, isError } = useCatalogueIndex(HOMEPAGE_CATALOGUE_FILTERS);
	const totalLists = data?.pagination.total ?? 0;

	const lists = data?.items ?? [];

	if (isPending) {
		return (
			<>
				<div className="d-flex justify-content-between align-items-center w-100 my-3">
					<h3>Latest Catalogues</h3>
					<div>
						<Link to="/catalogues" className="homepage-section-title">
							Read All Catalogues
						</Link>
					</div>
				</div>
				<div className="row">
					{Array.from({ length: HOMEPAGE_CATALOGUE_SKELETON_COUNT }).map((_, index) => (
						<CatalogueCardSkeleton key={index} />
					))}
				</div>
			</>
		);
	}

	if (isError) {
		const errorMessage = error instanceof Error ? error.message : 'Failed to load catalogues';

		return <div className="text-danger">Error: {errorMessage}</div>;
	}

	return (
		<>
			<div className="d-flex justify-content-between align-items-center w-100 my-3">
				<h3>Latest Catalogues total of {totalLists}</h3>
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

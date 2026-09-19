import React from 'react';
import { Link } from 'react-router-dom';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useCatalogueIndex } from '@/api/generated/catalogue/catalogue';
import { CatalogueCard } from '@/components/features/catalogues/CatalogueCard/CatalogueCard';
import { CatalogueCardSkeleton } from '@/components/features/catalogues/CatalogueCard/CatalogueCardSkeleton';
import { Cluster, Grid } from '@/components/shared/layout';
import styles from './ExploreList.module.css';

const HOMEPAGE_CATALOGUE_SKELETON_COUNT = 4;

/** Bootstrap `col-6 col-md-4 col-lg-3` → 2 / 3 / 4 cards per row. */
const CARD_COLUMNS = { base: 2, sm: 3, md: 4 };

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
				<Cluster justify="between" className={styles.header}>
					<h3 className={styles.title}>Latest Catalogues</h3>
					<Link to="/catalogues" className={styles.sectionLink}>
						Read All Catalogues
					</Link>
				</Cluster>
				<Grid as="ul" columns={CARD_COLUMNS} gap="lg" className={styles.cards}>
					{Array.from({ length: HOMEPAGE_CATALOGUE_SKELETON_COUNT }).map((_, index) => (
						<Grid.Item as="li" span="auto" key={index}>
							<CatalogueCardSkeleton />
						</Grid.Item>
					))}
				</Grid>
			</>
		);
	}

	if (isError) {
		const errorMessage = error instanceof Error ? error.message : 'Failed to load catalogues';

		return <p className={styles.error}>Error: {errorMessage}</p>;
	}

	return (
		<>
			<Cluster justify="between" className={styles.header}>
				<h3 className={styles.title}>Latest Catalogues total of {totalLists}</h3>
				<Link to="/catalogues" className={styles.sectionLink}>
					Read All Catalogues
				</Link>
			</Cluster>
			<Grid as="ul" columns={CARD_COLUMNS} gap="lg" className={styles.cards}>
				{lists.map((catalogue) => (
					<Grid.Item as="li" span="auto" key={catalogue.uuid}>
						<CatalogueCard catalogue={catalogue} />
					</Grid.Item>
				))}
			</Grid>
		</>
	);
};

export default ExploreCatalogueList;

import React from 'react';
import { CorpusStatsTiles } from '@/components/features/Homepage/CorpusStatsTiles';
import { LatestArticles } from '@/components/features/Homepage/LatestArticles';
import { PhotoBand } from '@/components/features/Homepage/PhotoBand';
import { PopularCatalogues } from '@/components/features/Homepage/PopularCatalogues';
import { ScopedSearch } from '@/components/features/Homepage/ScopedSearch';
import { Container, Grid, Stack } from '@/components/shared/layout';
import styles from './Homepage.module.css';

const LIST_COLUMNS = { base: 1, sm: 2 };

/**
 * Search-first landing page, the same for guests and signed-in users. Sign-up lives in the
 * Header; each section handles its own loading and error states.
 */
const Homepage: React.FC = () => (
	<Container className={styles.page}>
		<Stack gap="xl">
			<div className={styles.top}>
				<h1 className={styles.title}>Find your next reading</h1>
				<ScopedSearch className={styles.search} />
			</div>

			<CorpusStatsTiles />

			<PhotoBand />

			<Grid columns={LIST_COLUMNS} gap="xl">
				<Grid.Item span="auto">
					<LatestArticles />
				</Grid.Item>
				<Grid.Item span="auto">
					<PopularCatalogues />
				</Grid.Item>
			</Grid>
		</Stack>
	</Container>
);

export default Homepage;

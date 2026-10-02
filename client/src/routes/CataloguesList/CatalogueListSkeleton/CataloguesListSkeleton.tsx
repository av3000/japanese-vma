import classNames from 'classnames';
import { CatalogueCardSkeleton } from '@/components/features/LibraryCards/CatalogueCard/CatalogueCardSkeleton';
import skeletonStyles from '@/components/features/LibraryCards/LibraryCardSkeleton.module.css';
import { Grid, Stack } from '@/components/shared/layout';
import styles from './CataloguesListSkeleton.module.css';

const CATALOGUES_LIST_SKELETON_COUNT = 12;

// Mirrors the column spans of the SearchBar controls (keyword, filter, sort, submit).
const SEARCH_PLACEHOLDERS = [
	{ key: 'keyword', span: { base: 12, sm: 6, md: 4 } },
	{ key: 'filter', span: { base: 12, sm: 6, md: 4 } },
	{ key: 'sort', span: { base: 6, sm: 3, md: 2 } },
	{ key: 'submit', span: { base: 6, sm: 3, md: 2 } },
];

const SearchControlSkeleton = () => (
	<span className={classNames(skeletonStyles.block, skeletonStyles.line, styles.control)} />
);

const CataloguesListSkeleton = () => (
	<Stack gap="md" data-testid="catalogues-list-skeleton" aria-hidden="true">
		<Grid columns={12} gap="sm">
			{SEARCH_PLACEHOLDERS.map((placeholder) => (
				<Grid.Item key={placeholder.key} span={placeholder.span}>
					<SearchControlSkeleton />
				</Grid.Item>
			))}
		</Grid>

		<Grid columns={12} gap="lg">
			{Array.from({ length: CATALOGUES_LIST_SKELETON_COUNT }).map((_, index) => (
				<Grid.Item key={index} span={{ base: 6, sm: 4, md: 3 }}>
					<CatalogueCardSkeleton />
				</Grid.Item>
			))}
		</Grid>
	</Stack>
);

export default CataloguesListSkeleton;

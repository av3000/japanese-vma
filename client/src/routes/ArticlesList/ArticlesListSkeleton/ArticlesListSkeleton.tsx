import classNames from 'classnames';
import ArticleCardSkeleton from '@/components/shared/ArticleCard/ArticleCardSkeleton';
import skeletonStyles from '@/components/shared/ArticleCard/ArticleCardSkeleton.module.css';
import { Container, Grid, Stack, type Responsive } from '@/components/shared/layout';
import styles from './ArticlesListSkeleton.module.css';

const ARTICLES_LIST_SKELETON_COUNT = 12;

const SEARCH_PLACEHOLDERS: Array<{ key: string; span: Responsive<number>; short?: boolean }> = [
	{ key: 'search', span: { base: 12, sm: 6, md: 4 } },
	{ key: 'filter', span: { base: 12, sm: 4, md: 4 } },
	{ key: 'sort', span: { base: 4, sm: 2, md: 2 } },
	{ key: 'submit', span: { base: 12, md: 2 }, short: true },
];

const SearchControlSkeleton = ({ short }: { short?: boolean }) => (
	<span
		className={classNames(styles.control, short && styles.controlShort, skeletonStyles.block, skeletonStyles.line)}
	/>
);

const ArticlesListSkeleton = () => (
	<Container className={styles.page} data-testid="articles-list-skeleton" aria-hidden="true">
		<Stack gap="md">
			<Grid columns={12} gap="sm">
				{SEARCH_PLACEHOLDERS.map((placeholder) => (
					<Grid.Item key={placeholder.key} span={placeholder.span}>
						<SearchControlSkeleton short={placeholder.short} />
					</Grid.Item>
				))}
			</Grid>

			<span className={classNames(styles.countLine, skeletonStyles.block, skeletonStyles.line)} />

			<Grid as="ul" columns={{ base: 2, sm: 3, md: 4 }} gap="lg" className={styles.list}>
				{Array.from({ length: ARTICLES_LIST_SKELETON_COUNT }).map((_, index) => (
					<Grid.Item as="li" span="auto" key={index}>
						<ArticleCardSkeleton />
					</Grid.Item>
				))}
			</Grid>
		</Stack>
	</Container>
);

export default ArticlesListSkeleton;

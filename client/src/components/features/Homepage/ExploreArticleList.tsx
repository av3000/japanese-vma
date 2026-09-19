import React from 'react';
import { Link } from 'react-router-dom';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import ArticleCard from '@/components/shared/ArticleCard';
import ArticleCardSkeleton from '@/components/shared/ArticleCard/ArticleCardSkeleton';
import { Cluster, Grid } from '@/components/shared/layout';
import styles from './ExploreList.module.css';

const HOMEPAGE_ARTICLE_SKELETON_COUNT = 4;

/** Bootstrap `col-6 col-md-4 col-lg-3` → 2 / 3 / 4 cards per row. */
const CARD_COLUMNS = { base: 2, sm: 3, md: 4 };

const ExploreArticleList: React.FC = () => {
	const { articles, total, error, isPending, isError } = useInfiniteArticles({
		// No facet controls here, so do not make the server aggregate them.
		filters: { per_page: 4, include_facets: false },
	});

	if (isPending) {
		return (
			<>
				<Cluster justify="between" className={styles.header}>
					<h3 className={styles.title}>Latest Articles</h3>
					<Link to="/articles" className={styles.sectionLink}>
						Read All Articles
					</Link>
				</Cluster>
				<Grid as="ul" columns={CARD_COLUMNS} gap="lg" className={styles.cards}>
					{Array.from({ length: HOMEPAGE_ARTICLE_SKELETON_COUNT }).map((_, index) => (
						<Grid.Item as="li" span="auto" key={index}>
							<ArticleCardSkeleton />
						</Grid.Item>
					))}
				</Grid>
			</>
		);
	}

	if (isError) {
		return <p className={styles.error}>Error: {error.message}</p>;
	}

	return (
		<>
			<Cluster justify="between" className={styles.header}>
				<h3 className={styles.title}>
					Latest Articles {articles.length} of {total}
				</h3>
				<Link to="/articles" className={styles.sectionLink}>
					Read All Articles
				</Link>
			</Cluster>
			{articles.length === 0 ? (
				<p>No articles found.</p>
			) : (
				<Grid as="ul" columns={CARD_COLUMNS} gap="lg" className={styles.cards}>
					{articles.map((article) => (
						<Grid.Item as="li" span="auto" key={article.id}>
							<ArticleCard article={article} />
						</Grid.Item>
					))}
				</Grid>
			)}
		</>
	);
};

export default ExploreArticleList;

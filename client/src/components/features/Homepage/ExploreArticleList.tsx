import React from 'react';
import { Link } from 'react-router-dom';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import ArticleCard from '@/components/shared/ArticleCard';
import ArticleCardSkeleton from '@/components/shared/ArticleCard/ArticleCardSkeleton';

const HOMEPAGE_ARTICLE_SKELETON_COUNT = 4;

const ExploreArticleList: React.FC = () => {
	const { articles, total, error, isPending, isError } = useInfiniteArticles({
		filters: { per_page: 4 },
	});

	if (isPending) {
		return (
			<>
				<div className="d-flex justify-content-between align-items-center w-100 my-3">
					<h3>Latest Articles</h3>
					<div>
						<Link to="/articles" className="homepage-section-title">
							Read All Articles
						</Link>
					</div>
				</div>
				<div className="row">
					{Array.from({ length: HOMEPAGE_ARTICLE_SKELETON_COUNT }).map((_, index) => (
						<div key={index} className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
							<ArticleCardSkeleton />
						</div>
					))}
				</div>
			</>
		);
	}

	if (isError) {
		return <div className="text-danger">Error: {error.message}</div>;
	}

	return (
		<>
			<div className="d-flex justify-content-between align-items-center w-100 my-3">
				<h3>
					Latest Articles {articles.length} of {total}
				</h3>
				<div>
					<Link to="/articles" className="homepage-section-title">
						Read All Articles
					</Link>
				</div>
			</div>
			<div className="row">
				{articles.length === 0 ? (
					<p>No articles found.</p>
				) : (
					<>
						{articles.map((article) => (
							<div key={article.id} className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
								<ArticleCard article={article} />
							</div>
						))}
					</>
				)}
			</div>
		</>
	);
};

export default ExploreArticleList;

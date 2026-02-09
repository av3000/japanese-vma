import React from 'react';
import { Link } from 'react-router-dom';
import { useInfiniteQuery } from '@tanstack/react-query';
import { fetchArticles } from '@/api/articles/articles';
import Spinner from '@/assets/images/spinner.gif';
import ArticleCard from '@/components/shared/ArticleCard';

const ExploreArticleList: React.FC = () => {
	const { data, error, status } = useInfiniteQuery({
		// TODO: query keys should be managed centrally
		queryKey: ['articles'],
		queryFn: ({ pageParam }) => fetchArticles({ per_page: 4 }, pageParam),
		initialPageParam: 1,
		getNextPageParam: (lastPage) => {
			return lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;
		},
	});

	const allArticles = data?.pages.flatMap((page) => page.items) || [];
	const totalCount = data?.pages[0]?.pagination.total || 0;

	if (status === 'pending') {
		return (
			<div className="text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (status === 'error') {
		return <div className="text-danger">Error: {error.message}</div>;
	}

	return (
		<>
			<div className="d-flex justify-content-between align-items-center w-100 my-3">
				<h3>
					Latest Articles {allArticles.length} of {totalCount}
				</h3>
				<div>
					<Link to="/articles" className="homepage-section-title">
						Read All Articles
					</Link>
				</div>
			</div>
			<div className="row">
				{allArticles.length === 0 ? (
					<p>No articles found.</p>
				) : (
					<div className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
						{allArticles.map((article) => (
							<ArticleCard key={article.id} {...article} />
						))}
					</div>
				)}
			</div>
		</>
	);
};

export default ExploreArticleList;

import React from 'react';
import { useParams } from 'react-router-dom';
import { useArticleQuery } from '@/api/articles/details';
import ArticleContent from './ArticleContent';
import ArticleDetailsSkeleton from './ArticleDetailsSkeleton';

const ArticleDetails: React.FC = () => {
	const { article_id } = useParams<{ article_id: string }>();

	const { data: article, isLoading, isError } = useArticleQuery(article_id);

	if (isLoading) {
		return <ArticleDetailsSkeleton />;
	}

	if (isError || !article) {
		return (
			<div className="container mt-5 text-center">
				<p className="lead">Article not found or was deleted.</p>
				<a href="/articles" className="btn btn-link">
					Back to all Articles
				</a>
			</div>
		);
	}

	return <ArticleContent article={article} />;
};
export default ArticleDetails;

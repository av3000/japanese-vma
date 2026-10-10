import React from 'react';
import { useParams } from 'react-router-dom';
import { useArticleQuery } from '@/api/articles/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import ArticleContent from './ArticleContent';
import ArticleDetailsSkeleton from './ArticleDetailsSkeleton';

const ArticleDetails: React.FC = () => {
	const { article_id } = useParams<{ article_id: string }>();

	const { data: article, isLoading, isError, error } = useArticleQuery(article_id);

	if (isLoading) {
		return <PageLoading family="detail" visual={<ArticleDetailsSkeleton />} />;
	}

	if (isError || !article) {
		return (
			<DetailUnavailable
				message={unavailableMessage(error, 'article')}
				backTo="/articles"
				backLabel="Back to Articles"
			/>
		);
	}

	return <ArticleContent article={article} />;
};
export default ArticleDetails;

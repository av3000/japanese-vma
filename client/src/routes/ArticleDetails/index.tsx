import React from 'react';
import { Navigate, useParams, useSearchParams } from 'react-router-dom';
import { useArticleQuery } from '@/api/articles/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import ArticleContent from './ArticleContent';
import ArticleDetailsSkeleton from './ArticleDetailsSkeleton';

const ArticleDetails: React.FC = () => {
	const { article_id } = useParams<{ article_id: string }>();
	const [searchParams] = useSearchParams();

	const { data: article, isLoading, isError, error } = useArticleQuery(article_id);

	// Edit used to open as a modal from `?edit=1`; old links now land on the edit page.
	if (article_id && searchParams.get('edit') === '1') {
		return <Navigate to={`/articles/${article_id}/edit`} replace />;
	}

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

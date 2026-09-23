import React from 'react';
import { useParams } from 'react-router-dom';
import { useArticleQuery } from '@/api/articles/details';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container } from '@/components/shared/layout';
import ArticleContent from './ArticleContent';
import styles from './ArticleDetails.module.css';
import ArticleDetailsSkeleton from './ArticleDetailsSkeleton';

const ArticleDetails: React.FC = () => {
	const { article_id } = useParams<{ article_id: string }>();

	const { data: article, isLoading, isError } = useArticleQuery(article_id);

	if (isLoading) {
		return <PageLoading family="detail" visual={<ArticleDetailsSkeleton />} />;
	}

	if (isError || !article) {
		return (
			<Container size="sm" as="section" className={styles.notFound}>
				<p className={styles.lead}>Article not found or was deleted.</p>
				<Button href="/articles" variant="linkButton">
					Back to all Articles
				</Button>
			</Container>
		);
	}

	return <ArticleContent article={article} />;
};
export default ArticleDetails;

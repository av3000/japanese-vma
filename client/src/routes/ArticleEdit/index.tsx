import React from 'react';
import { Navigate, useParams } from 'react-router-dom';

const ArticleEditRedirect: React.FC = () => {
	const { article_id } = useParams<{ article_id: string }>();

	if (!article_id) {
		return <Navigate to="/articles" replace />;
	}

	return <Navigate to={`/articles/${article_id}?edit=1`} replace />;
};

export default ArticleEditRedirect;

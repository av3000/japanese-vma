import React from 'react';
import { ARTICLE_STATUS } from '@/api/articles/moderation';
import { Badge } from './badge';

interface ArticleStatusProps {
	status: number;
}

const ArticleStatus: React.FC<ArticleStatusProps> = ({ status }) => {
	switch (status) {
		case ARTICLE_STATUS.PENDING:
			return <Badge variant="pending">Approval: Pending</Badge>;
		case ARTICLE_STATUS.PROCESSED:
			return <Badge variant="secondary">Approval: Processed</Badge>;
		case ARTICLE_STATUS.REVIEWING:
			return <Badge variant="pending">Approval: Reviewing</Badge>;
		case ARTICLE_STATUS.REJECTED:
			return <Badge variant="destructive">Approval: Rejected</Badge>;
		case ARTICLE_STATUS.APPROVED:
			return <Badge variant="success">Approval: Approved</Badge>;
		default:
			return <Badge variant="secondary">Approval: Pending</Badge>;
	}
};

export default ArticleStatus;

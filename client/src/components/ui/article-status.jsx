import React from 'react';
import { Badge } from './badge';

const ArticleStatusTypes = {
	PENDING: 0,
	REVIEWING: 1,
	REJECTED: 2,
	APPROVED: 3,
};

const ArticleStatus = ({ status }) => {
	switch (status) {
		case ArticleStatusTypes.PENDING:
			return (
				<Badge variant="pending">
					Pending
				</Badge>
			);
		case ArticleStatusTypes.REVIEWING:
			return (
				<Badge variant="pending">
					Reviewing
				</Badge>
			);
		case ArticleStatusTypes.REJECTED:
			return (
				<Badge variant="destructive">
					Rejected
				</Badge>
			);
		case ArticleStatusTypes.APPROVED:
			return (
				<Badge variant="success">
					Approved
				</Badge>
			);
		default:
			return (
				<Badge variant="secondary">
					Pending
				</Badge>
			);
	}
};

export default ArticleStatus;

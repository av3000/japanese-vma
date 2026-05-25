import React from 'react';
import { Badge } from './badge';

const ArticleStatusTypes = {
	PENDING: 0,
	REVIEWING: 1,
	REJECTED: 2,
	APPROVED: 3,
};

// TOOD: convert to .tsx and add type
const ArticleStatus = ({ status }) => {
	switch (status) {
		case ArticleStatusTypes.PENDING:
			return <Badge variant="pending">Approval: Pending</Badge>;
		case ArticleStatusTypes.REVIEWING:
			return <Badge variant="pending">Approval: Reviewing</Badge>;
		case ArticleStatusTypes.REJECTED:
			return <Badge variant="destructive">Approval: Rejected</Badge>;
		case ArticleStatusTypes.APPROVED:
			return <Badge variant="success">Approval: Approved</Badge>;
		default:
			return <Badge variant="secondary">Approval: Pending</Badge>;
	}
};

export default ArticleStatus;

import React from 'react';
import { Chip } from '../shared/Chip';

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
				<Chip readonly={true} variant="secondary-outline">
					Pending
				</Chip>
			);
		case ArticleStatusTypes.REVIEWING:
			return (
				<Chip readonly={true} variant="secondary-outline">
					Reviewing
				</Chip>
			);
		case ArticleStatusTypes.REJECTED:
			return (
				<Chip readonly={true} variant="danger">
					Rejected
				</Chip>
			);
		case ArticleStatusTypes.APPROVED:
			return (
				<Chip readonly={true} variant="success">
					Approved
				</Chip>
			);
		default:
			return (
				<Chip readonly={true} variant="secondary-outline">
					Pending
				</Chip>
			);
	}
};

export default ArticleStatus;

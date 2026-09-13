import React from 'react';
import { ApiComment as Comment } from '@/api/comments';
import { Alert } from '@/components/shared/Alert';
import { Badge } from '@/components/ui/badge';
import CommentItem from '../CommentItem/CommentItem';
import styles from './CommentList.module.css';

interface User {
	id: string | number;
	name: string;
	is_admin?: boolean;
}

interface CommentListProps {
	comments: Comment[];
	currentUser: User | null;
	onDelete: (commentId: number) => void;
	onLike: (commentId: number) => void;
	/** Pending state is per comment - liking one must not disable the whole thread. */
	isLikePending: (commentId: number) => boolean;
}

const CommentList: React.FC<CommentListProps> = ({ comments, currentUser, onDelete, onLike, isLikePending }) => {
	return (
		<div>
			<h5 className={styles.heading}>
				<Badge variant="secondary">{comments.length}</Badge>
				{'  '}
				Comment{comments.length !== 1 ? 's' : ''}
			</h5>

			{comments.length === 0 ? (
				<Alert tone="info" className={styles.empty}>
					Be the first to comment
				</Alert>
			) : (
				<ul className={styles.items}>
					{comments.map((comment) => (
						<CommentItem
							key={comment.id}
							comment={comment}
							currentUser={currentUser}
							onDelete={() => onDelete(comment.id)}
							onLike={() => onLike(comment.id)}
							isLikePending={isLikePending(comment.id)}
						/>
					))}
				</ul>
			)}
		</div>
	);
};

export default CommentList;

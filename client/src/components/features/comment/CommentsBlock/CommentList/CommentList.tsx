import React from 'react';
import { ApiComment as Comment } from '@/api/comments';
import CommentItem from '../CommentItem/CommentItem';

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
			<h5 className="text-muted mb-4 mt-4">
				<span className="badge badge-secondary">{comments.length}</span>
				{'  '}
				Comment{comments.length !== 1 ? 's' : ''}
			</h5>

			{comments.length === 0 ? (
				<div className="alert text-center alert-info">Be the first to comment</div>
			) : (
				comments.map((comment) => (
					<CommentItem
						key={comment.id}
						comment={comment}
						currentUser={currentUser}
						onDelete={() => onDelete(comment.id)}
						onLike={() => onLike(comment.id)}
						isLikePending={isLikePending(comment.id)}
					/>
				))
			)}
		</div>
	);
};

export default CommentList;

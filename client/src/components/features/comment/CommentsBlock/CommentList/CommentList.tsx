import React from 'react';
import { Comment } from '@/api/comments';
import CommentItem from '../CommentItem/CommentItem';

interface User {
	id: string | number;
	name: string;
	is_admin?: boolean;
}

interface CommentListProps {
	comments: Comment[];
	currentUser: User | null;
	onDelete: (commentId: string | number) => void;
	onLike: (commentId: string | number) => void;
}

const CommentList: React.FC<CommentListProps> = ({ comments, currentUser, onDelete, onLike }) => {
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
					/>
				))
			)}
		</div>
	);
};

export default CommentList;

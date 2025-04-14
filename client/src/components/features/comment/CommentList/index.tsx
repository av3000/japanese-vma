// @ts-nocheck
/* eslint-disable */
import React from 'react';
import Comment from '../CommentItem';

interface User {
	id: string | number;
	username?: string;
	// Add other user properties as needed
}

interface CommentType {
	id: string | number;
	content?: string;
	user?: User;
	created_at?: string;
	updated_at?: string;
	likes?: number;
	// Add other comment properties as needed
}

interface CommentListProps {
	comments?: CommentType[];
	currentUser: User;
	objectId: string | number;
	deleteComment: (id: string | number) => void;
	editComment: (id: string | number) => void;
	likeComment: (id: string | number) => void;
}

const CommentList: React.FC<CommentListProps> = ({
	comments = [],
	currentUser,
	objectId,
	deleteComment,
	editComment,
	likeComment,
}) => {
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
					<Comment
						key={comment.id}
						currentUser={currentUser}
						comment={comment}
						objectId={objectId}
						deleteComment={() => deleteComment(comment.id)}
						editComment={() => editComment(comment.id)}
						likeComment={() => likeComment(comment.id)}
					/>
				))
			)}
		</div>
	);
};

export default CommentList;

import React, { useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import CommentForm from './CommentForm/CommentForm';
import CommentList from './CommentList/CommentList';

interface Comment {
	id: string | number;
	content: string;
	userName: string;
	user_id: string | number;
	created_at: string;
	likesTotal: number;
	isLiked: boolean;
}

interface CommentsBlockProps {
	objectId: string | number;
	objectType: 'article' | 'post' | 'list';
	initialComments: Comment[];
	isLocked?: boolean; // NEW: For locked posts
}

const CommentsBlock: React.FC<CommentsBlockProps> = ({
	objectId,
	objectType,
	initialComments,
	isLocked = false, // NEW
}) => {
	const [comments, setComments] = useState<Comment[]>(initialComments);
	const [isSubmitting, setIsSubmitting] = useState(false);
	const { isAuthenticated, user } = useAuth();

	const handleAddComment = useCallback(
		async (content: string) => {
			if (!isAuthenticated || !user) return;

			setIsSubmitting(true);
			try {
				const response = await apiCall({
					method: HttpMethod.POST,
					path: `/api/${objectType}/${objectId}/comment`,
					data: { content },
				});

				const newComment: Comment = {
					...response.comment,
					userName: user.name,
				};

				setComments((prev) => [newComment, ...prev]);
			} catch (error) {
				console.error('Error adding comment:', error);
				throw error;
			} finally {
				setIsSubmitting(false);
			}
		},
		[isAuthenticated, user, objectType, objectId],
	);

	const handleDeleteComment = useCallback(
		async (commentId: string | number) => {
			try {
				await apiCall({
					method: HttpMethod.DELETE,
					path: `/api/${objectType}/${objectId}/comment/${commentId}`,
				});

				setComments((prev) => prev.filter((comment) => comment.id !== commentId));
			} catch (error) {
				console.error('Error deleting comment:', error);
			}
		},
		[objectType, objectId],
	);

	const handleLikeComment = useCallback(
		async (commentId: string | number) => {
			if (!isAuthenticated) return;

			try {
				const comment = comments.find((c) => c.id === commentId);
				if (!comment) return;

				const endpoint = comment.isLiked ? 'unlike' : 'like';

				await apiCall({
					method: HttpMethod.POST,
					path: `/api/${objectType}/${objectId}/comment/${commentId}/${endpoint}`,
				});

				setComments((prev) =>
					prev.map((c) =>
						c.id === commentId
							? {
									...c,
									isLiked: !c.isLiked,
									likesTotal: c.likesTotal + (endpoint === 'like' ? 1 : -1),
								}
							: c,
					),
				);
			} catch (error) {
				console.error('Error liking comment:', error);
			}
		},
		[isAuthenticated, comments, objectType, objectId],
	);

	return (
		<div>
			<hr />
			{/* NEW: Handle locked state */}
			{isLocked ? (
				<h6 className="alert alert-warning">This post is locked and new comments are not allowed.</h6>
			) : isAuthenticated && user ? (
				<>
					<h6>Share what's on your mind</h6>
					<CommentForm onSubmit={handleAddComment} isSubmitting={isSubmitting} />
				</>
			) : (
				<h6>
					You need to <Link to="/login">login</Link> to comment
				</h6>
			)}

			<CommentList
				comments={comments}
				currentUser={user}
				onDelete={handleDeleteComment}
				onLike={handleLikeComment}
			/>
		</div>
	);
};

export default CommentsBlock;

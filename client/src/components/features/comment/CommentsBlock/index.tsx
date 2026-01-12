import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { addComment, Comment, deleteComment, fetchComments, toggleCommentLike } from '@/api/comments';
import { useAuth } from '@/hooks/useAuth';
import CommentForm from './CommentForm/CommentForm';
import CommentList from './CommentList/CommentList';

interface CommentsBlockProps {
	objectUuid: string;
	objectId: string | number;
	objectType: 'article' | 'post' | 'list';
	isLocked?: boolean;
}

const CommentsBlock: React.FC<CommentsBlockProps> = ({ objectUuid, objectId, objectType, isLocked = false }) => {
	const { isAuthenticated, user } = useAuth();

	const queryClient = useQueryClient();
	const queryKey = ['comments', objectType, objectId, { include_likes: true }];
	const { data: comments = [], isLoading } = useQuery({
		queryKey,
		queryFn: () => fetchComments(objectType, objectUuid, { include_likes: true }),
		enabled: !!objectUuid,
		select: (data) => {
			return data.items.map((comment) => ({
				...comment,
				isLiked: comment.likes?.some((l) => l.user.id === user?.id),
				likesTotal: comment.likes?.length || 0,
			}));
		},
	});

	const addMutation = useMutation({
		mutationFn: (content: string) => addComment(objectType, objectId, content),
		onSuccess: (newComment) => {
			queryClient.setQueryData(queryKey, (old: Comment[]) => [
				{ ...newComment, userName: user?.name, isLiked: false, likesTotal: 0 },
				...(old || []),
			]);
		},
	});

	const deleteMutation = useMutation({
		mutationFn: (commentId: number) => deleteComment(objectType, objectId, commentId),
		onMutate: async (commentId) => {
			await queryClient.cancelQueries({ queryKey });
			const previousComments = queryClient.getQueryData(queryKey);

			// Optimistic update - instant removal
			queryClient.setQueryData(queryKey, (old: Comment[]) => old?.filter((c) => c.id !== commentId));

			return { previousComments };
		},
		onError: (_err, _vars, context) => {
			queryClient.setQueryData(queryKey, context?.previousComments);
		},
	});

	// TODO: create backend endpoint for updating single comment instance after like action.
	const likeMutation = useMutation({
		mutationFn: ({ id, isLiked }: { id: number; isLiked: boolean }) =>
			toggleCommentLike(objectType, objectId, id, isLiked),

		onMutate: async ({ id }) => {
			await queryClient.cancelQueries({ queryKey });
			const previousData = queryClient.getQueryData(queryKey);

			queryClient.setQueryData(queryKey, (oldData: any) => {
				if (!oldData?.items) return oldData;

				// Optimistic instant update
				return {
					...oldData,
					items: oldData.items.map((c: any) => {
						if (c.id !== id) return c;

						const isCurrentlyLiked = c.likes?.some((l: any) => l.user_id === user?.id);

						let newLikes = [...(c.likes || [])];

						if (isCurrentlyLiked) {
							newLikes = newLikes.filter((l: any) => l.user_id !== user?.id);
						} else {
							newLikes.push({ user_id: user?.id, created_at: new Date().toISOString() });
						}

						return { ...c, likes: newLikes };
					}),
				};
			});

			return { previousData };
		},
		onError: (_err, _vars, context: any) => {
			if (context?.previousData) {
				queryClient.setQueryData(queryKey, context.previousData);
			}
		},
	});

	return (
		<div>
			<hr />
			{isLocked ? (
				<h6 className="alert alert-warning">This post is locked and new comments are not allowed.</h6>
			) : isAuthenticated && user ? (
				<>
					<h6>Share what's on your mind</h6>
					<CommentForm onSubmit={(content) => addMutation.mutateAsync(content)} isLoading={isLoading} />
				</>
			) : (
				<h6>
					You need to <Link to="/login">login</Link> to comment
				</h6>
			)}

			<CommentList
				comments={comments}
				currentUser={user}
				onDelete={(id) => deleteMutation.mutate(Number(id))}
				onLike={(id) => {
					const comment = comments.find((c) => c.id === Number(id));
					if (comment) likeMutation.mutate({ id: Number(id), isLiked: comment.isLiked });
				}}
			/>
		</div>
	);
};

export default CommentsBlock;

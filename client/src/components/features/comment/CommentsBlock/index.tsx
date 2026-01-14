import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { addComment, AddCommentPayload, deleteComment, fetchComments, toggleCommentLike } from '@/api/comments';
import { useAuth } from '@/hooks/useAuth';
import CommentForm from './CommentForm/CommentForm';
import CommentList from './CommentList/CommentList';

interface CommentsBlockProps {
	objectUuid: string;
	objectId: string | number;
	objectType: 'article' | 'post' | 'list';
	objectTypeId: number;
	isLocked?: boolean;
}

const CommentsBlock: React.FC<CommentsBlockProps> = ({
	objectUuid,
	objectId,
	objectType,
	objectTypeId,
	isLocked = false,
}) => {
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
		mutationFn: (requestPayload: AddCommentPayload) => addComment(objectType, objectId, requestPayload),
		onSuccess: (newComment) => {
			queryClient.setQueryData(queryKey, (oldData: any) => {
				if (!oldData) return oldData;

				return {
					...oldData,
					items: [
						{
							...newComment,
							userName: user?.name,
							likes: [],
						},
						...(oldData.items || []),
					],
				};
			});
		},
	});

	const deleteMutation = useMutation({
		mutationFn: (commentId: number) => deleteComment({ objectType, objectTypeId, commentId }),
		onMutate: async (commentId) => {
			await queryClient.cancelQueries({ queryKey });
			const previousComments = queryClient.getQueryData(queryKey);

			queryClient.setQueryData(queryKey, (oldData: any) => {
				if (!oldData?.items) return oldData;

				return {
					...oldData,
					items: oldData.items.filter((c: any) => c.id !== commentId),
				};
			});

			return { previousComments };
		},
		onError: (_err, _vars, context) => {
			if (context?.previousComments) {
				queryClient.setQueryData(queryKey, context.previousComments);
			}
		},
	});

	const likeMutation = useMutation({
		mutationFn: ({ id }: { id: number }) => toggleCommentLike({ objectType, objectTypeId, commentId: id }),

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

						const isCurrentlyLiked = c.likes?.some((l: any) => l.user?.id === user?.id);
						let newLikes = [...(c.likes || [])];

						if (isCurrentlyLiked) {
							newLikes = newLikes.filter((l: any) => l.user?.id !== user?.id);
						} else {
							newLikes.push({
								id: 'temp-id',
								user: { id: user?.id, name: user?.name },
								created_at: new Date().toISOString(),
							});
						}

						return { ...c, likes: newLikes };
					}),
				};
			});

			return { previousData };
		},
		onSuccess: (serverResult, variables) => {
			const commentId = variables.id;

			queryClient.setQueryData(queryKey, (oldData: any) => {
				if (!oldData?.items) return oldData;

				return {
					...oldData,
					items: oldData.items.map((c: any) => {
						if (c.id !== commentId) return c;

						const otherLikes = c.likes.filter((l: any) => l.user?.id !== user?.id);

						const newLikes =
							serverResult && typeof serverResult === 'object'
								? [...otherLikes, serverResult]
								: otherLikes;

						return { ...c, likes: newLikes };
					}),
				};
			});
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
					<CommentForm
						onSubmit={(content) => addMutation.mutateAsync({ content, entity_id: objectUuid })}
						isLoading={isLoading}
					/>
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
					if (comment) likeMutation.mutate({ id: Number(id) });
				}}
			/>
		</div>
	);
};

export default CommentsBlock;

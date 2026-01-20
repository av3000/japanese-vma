import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { addComment, AddCommentPayload, deleteComment, fetchComments } from '@/api/comments';
import { LikeResponse, toggleCommentLike } from '@/api/likes/likes';
import { useAuth } from '@/hooks/useAuth';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import CommentForm from './CommentForm/CommentForm';
import CommentList from './CommentList/CommentList';

interface CommentsBlockProps {
	objectUuid: string;
	parentObjectId: string | number;
	parentObjectType: 'article' | 'post' | 'list';
	isLocked?: boolean;
}

const CommentsBlock: React.FC<CommentsBlockProps> = ({
	objectUuid,
	parentObjectId,
	parentObjectType,
	isLocked = false,
}) => {
	const { isAuthenticated, user } = useAuth();

	const queryClient = useQueryClient();
	const queryKey = ['comments', parentObjectType, parentObjectId, { include_likes: true }];
	const { data: comments = [], isLoading } = useQuery({
		queryKey,
		queryFn: () => fetchComments(parentObjectType, objectUuid, { include_likes: true }),
		enabled: !!objectUuid,
		select: (data) => {
			return data.items.map((comment) => ({
				...comment,
			}));
		},
	});

	const addMutation = useMutation({
		mutationFn: (requestPayload: AddCommentPayload) => addComment(parentObjectType, parentObjectId, requestPayload),
		onSuccess: (newComment) => {
			queryClient.setQueryData(queryKey, (oldData: any) => {
				if (!oldData) return oldData;

				return {
					...oldData,
					items: [
						{
							...newComment,
							userName: user?.name,
						},
						...(oldData.items || []),
					],
				};
			});
		},
	});

	const deleteMutation = useMutation({
		mutationFn: (commentId: number) => deleteComment({ parentObjectType, parentObjectId, commentId }),
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

	// TODO: refetch only single comment that was liked
	const likeMutation = useMutation<LikeResponse, unknown, { id: number }>({
		mutationFn: ({ id }) =>
			toggleCommentLike({
				objectType: ObjectTemplateTypeLabel[ObjectTemplateType.COMMENT],
				objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.COMMENT],
				instanceId: id,
			}),

		onSuccess: () => {
			// refetch the comments list to get authoritative totals/flags
			queryClient.invalidateQueries({ queryKey });
		},

		onError: (err) => {
			// optional: notify user
			console.error('Like failed', err);
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
				isLoading={likeMutation.isPending}
			/>
		</div>
	);
};

export default CommentsBlock;

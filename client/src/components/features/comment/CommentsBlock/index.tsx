import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { addComment, deleteComment, fetchComments } from '@/api/comments';
import type { ObjectTemplateType as CommentEntityType } from '@/api/generated/model/objectTemplateType';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import { LikeResponse, toggleInstanceLike } from '@/api/likes/likes';
import { useAuth } from '@/hooks/useAuth';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import CommentForm from './CommentForm/CommentForm';
import CommentList from './CommentList/CommentList';

// TODO: 'parent' naming part is confusing alittle and not read-friendly, parent of what?, use more direct naming.
interface CommentsBlockProps {
	readObjectType: 'article' | 'post' | 'catalogue';
	readObjectUuid: string;
	entityId: number;
	entityType: CommentEntityType;
	entityUuid: string;
	isLocked?: boolean;
}

const CommentsBlock: React.FC<CommentsBlockProps> = ({
	readObjectType,
	readObjectUuid,
	entityId,
	entityType,
	entityUuid,
	isLocked = false,
}) => {
	const { isAuthenticated, user } = useAuth();

	const queryClient = useQueryClient();
	// TODO: query keys management should be somehow centralized
	const queryKey = ['comments', readObjectType, entityId, { include_likes: true }];
	const { data: comments = [], isLoading } = useQuery({
		queryKey,
		queryFn: () => fetchComments(readObjectType, readObjectUuid, { include_likes: true }),
		enabled: !!readObjectUuid,
		select: (data) => {
			return data.items.map((comment) => ({
				...comment,
			}));
		},
	});

	const addMutation = useMutation({
		mutationFn: (requestPayload: Pick<StoreCommentRequest, 'content' | 'parent_comment_id'>) =>
			addComment(entityType, entityId, entityUuid, requestPayload),
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
		mutationFn: (commentId: number) => deleteComment({ parentObjectType: readObjectType, parentObjectId: entityId, commentId }),
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
			toggleInstanceLike({
				objectType: ObjectTemplateTypeLabel[ObjectTemplateType.COMMENT],
				objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.COMMENT],
				instanceId: id,
			}),

		onSuccess: () => {
			// TODO: now it refetches the whole list for comments totals/flags, perhaps we could optimize it for local update with single comment fetch
			queryClient.invalidateQueries({ queryKey });
		},

		onError: (err) => {
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
						onSubmit={(content) => addMutation.mutateAsync({ content }).then(() => undefined)}
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

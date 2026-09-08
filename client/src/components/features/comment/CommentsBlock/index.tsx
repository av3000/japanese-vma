import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { addComment, deleteComment, fetchComments, getCommentsQueryKey, useLikeCommentMutation } from '@/api/comments';
import type { ObjectTemplateType as CommentEntityType } from '@/api/generated/model/objectTemplateType';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import { useAuth } from '@/hooks/useAuth';
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
	const queryKey = getCommentsQueryKey(readObjectType, entityId);
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
		mutationFn: (commentId: number) =>
			deleteComment({ parentObjectType: readObjectType, parentObjectId: entityId, commentId }),
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

	const likeMutation = useLikeCommentMutation(queryKey);

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
				onDelete={(id) => deleteMutation.mutate(id)}
				onLike={(id) => likeMutation.mutate(id)}
				isLikePending={likeMutation.isTogglingInstance}
			/>
		</div>
	);
};

export default CommentsBlock;

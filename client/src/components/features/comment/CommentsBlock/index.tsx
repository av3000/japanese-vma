import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
	COMMENT_LIST_PARAMS,
	COMMENT_PARENTS,
	getCommentsQueryKey,
	readCommentWriteError,
	useCreateCommentMutation,
	useDeleteCommentMutation,
	useLikeCommentMutation,
	useUpdateCommentMutation,
	type ApiComment,
	type ApiCommentReply,
	type CommentParent,
} from '@/api/comments';
import { useAuth } from '@/hooks/useAuth';
import CommentForm from './CommentForm/CommentForm';
import CommentList from './CommentList/CommentList';

interface CommentsBlockProps {
	/**
	 * Which kind of thing is being commented on. Selects the read transport, the
	 * cache key and the entity template in one go, so no caller has to keep a
	 * route segment and a template uuid in agreement.
	 */
	parent: CommentParent;
	entityId: number;
	entityUuid: string;
	isLocked?: boolean;
}

const CommentsBlock: React.FC<CommentsBlockProps> = ({ parent, entityId, entityUuid, isLocked = false }) => {
	const { isAuthenticated, user } = useAuth();

	const queryKey = getCommentsQueryKey(parent, entityUuid);
	const {
		data: thread,
		isLoading,
		isError,
	} = useQuery({
		queryKey,
		queryFn: () => COMMENT_PARENTS[parent].fetchThread(entityUuid, COMMENT_LIST_PARAMS),
		enabled: !!entityUuid,
	});

	const createMutation = useCreateCommentMutation({ parent, entityId, entityUuid });
	const updateMutation = useUpdateCommentMutation({ parent, entityUuid });
	const deleteMutation = useDeleteCommentMutation({ parent, entityUuid });
	const likeMutation = useLikeCommentMutation(queryKey);

	/** Which comment a failure belongs to, so one bad request does not shout on every row. */
	const [rowError, setRowError] = useState<{ commentId: number; message: string } | null>(null);

	const runOnRow = async (commentId: number, action: () => Promise<unknown>) => {
		setRowError(null);

		try {
			await action();
		} catch (error) {
			setRowError({ commentId, message: readCommentWriteError(error).message });
			throw error;
		}
	};

	const handleEdit = (comment: ApiComment | ApiCommentReply, content: string) =>
		runOnRow(comment.id, () => updateMutation.mutateAsync({ uuid: comment.uuid, content })).then(() => undefined);

	const handleDelete = (comment: ApiComment | ApiCommentReply) => {
		void runOnRow(comment.id, () =>
			deleteMutation.mutateAsync({
				id: comment.id,
				uuid: comment.uuid,
				parentCommentId: comment.parent_comment_id,
			}),
		).catch(() => undefined);
	};

	const handleReply = (rootCommentId: number, content: string) =>
		runOnRow(rootCommentId, () =>
			createMutation.mutateAsync({ content, parent_comment_id: rootCommentId }),
		).then(() => undefined);

	const stateFor = (commentId: number) => ({
		isLikePending: likeMutation.isTogglingInstance(commentId),
		isEditPending: updateMutation.isPending && updateMutation.variables?.uuid === findUuid(thread?.items, commentId),
		isDeletePending: deleteMutation.isPending && deleteMutation.variables?.id === commentId,
		isReplyPending: createMutation.isPending && createMutation.variables?.parent_comment_id === commentId,
		error: rowError?.commentId === commentId ? rowError.message : undefined,
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
						onSubmit={(content) => createMutation.mutateAsync({ content }).then(() => undefined)}
						isSubmitting={createMutation.isPending && createMutation.variables?.parent_comment_id == null}
					/>
				</>
			) : (
				<h6>
					You need to <Link to="/login">login</Link> to comment
				</h6>
			)}

			{isLoading ? (
				<p className="text-muted mt-4">Loading comments…</p>
			) : isError ? (
				<div className="alert alert-danger mt-4">Comments could not be loaded. Please try again.</div>
			) : (
				<CommentList
					comments={thread?.items ?? []}
					total={thread?.pagination.total ?? 0}
					hasMore={thread?.pagination.has_more ?? false}
					// Replying to a locked parent is refused by the API with a 409,
					// so the affordance is hidden rather than offered and rejected.
					canReply={isAuthenticated && !isLocked}
					stateFor={stateFor}
					onLike={(commentId) => likeMutation.mutate(commentId)}
					onDelete={handleDelete}
					onEdit={handleEdit}
					onReply={handleReply}
				/>
			)}
		</div>
	);
};

/** The edit transport is keyed by uuid; pending state is asked about by numeric id. */
const findUuid = (items: ApiComment[] | undefined, commentId: number): string | undefined => {
	for (const item of items ?? []) {
		if (item.id === commentId) return item.uuid;

		const reply = item.replies.find((candidate) => candidate.id === commentId);

		if (reply) return reply.uuid;
	}

	return undefined;
};

export default CommentsBlock;

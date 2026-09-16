import React, { useState } from 'react';
import type { ApiComment, ApiCommentReply } from '@/api/comments';
import DefaultAvatar from '@/assets/images/avatar-man.svg';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import CommentForm from '../CommentForm/CommentForm';

/** A comment outlives its author's account; the API drops `author` when the row is gone. */
const DELETED_AUTHOR_NAME = 'Deleted user';

/**
 * Per-comment pending state. Acting on one comment must not disable the whole
 * thread, which is the same rule the Like seam already follows.
 */
export interface CommentActionState {
	isLikePending: boolean;
	isEditPending: boolean;
	isDeletePending: boolean;
	isReplyPending: boolean;
	error?: string;
}

interface CommentItemProps {
	comment: ApiComment | ApiCommentReply;
	/** Replies are one visual level in; they do not nest further. */
	isReply?: boolean;
	canReply: boolean;
	state: CommentActionState;
	onLike: () => void;
	onDelete: () => void;
	onEdit: (content: string) => Promise<void>;
	onReply?: (content: string) => Promise<void>;
}

const CommentItem: React.FC<CommentItemProps> = ({
	comment,
	isReply = false,
	canReply,
	state,
	onLike,
	onDelete,
	onEdit,
	onReply,
}) => {
	const [isEditing, setIsEditing] = useState(false);
	const [isReplying, setIsReplying] = useState(false);

	// Straight off the resource: the API computes these from the same
	// CommentPolicy the write endpoints enforce, so there is no client-side copy
	// of the rules to fall out of step.
	const { can_edit: canEdit, can_delete: canDelete, is_liked: isLiked } = comment.viewer;

	const handleEdit = async (content: string) => {
		await onEdit(content);
		setIsEditing(false);
	};

	const handleReply = async (content: string) => {
		await onReply?.(content);
		setIsReplying(false);
	};

	return (
		<div className={`media ${isReply ? 'ml-5' : ''}`}>
			<img className="d-flex mr-3 rounder-circle" src={DefaultAvatar} alt="default-avatar" />
			<div className="media-body">
				<div className="d-flex justify-content-between align-items-center">
					<h5>@{comment.author?.name ?? DELETED_AUTHOR_NAME}</h5>
					<div className="d-flex align-items-center">
						{canEdit && !isEditing && (
							<Button
								onClick={() => setIsEditing(true)}
								variant="ghost"
								size="sm"
								aria-label="Edit this comment"
								disabled={state.isEditPending}
							>
								<Icon size="sm" name="penSolid" />
							</Button>
						)}
						{canDelete && (
							<Button
								onClick={onDelete}
								variant="ghost"
								size="sm"
								aria-label="Delete this comment"
								disabled={state.isDeletePending}
							>
								<Icon size="sm" name="trashbinSolid" />
							</Button>
						)}
					</div>
				</div>

				{isEditing ? (
					<CommentForm
						onSubmit={handleEdit}
						isSubmitting={state.isEditPending}
						initialValue={comment.content}
						submitLabel="Save"
						onCancel={() => setIsEditing(false)}
					/>
				) : (
					<div>{comment.content}</div>
				)}

				{state.error && <div className="alert alert-danger mt-2">{state.error}</div>}

				<br />
				<div className="text-muted d-flex align-items-center">
					<span className="mx-2">{comment.likes_count} likes</span>
					<Button
						onClick={onLike}
						variant="ghost"
						size="sm"
						aria-label={isLiked ? 'Unlike this comment' : 'Like this comment'}
						aria-pressed={isLiked}
						disabled={state.isLikePending}
					>
						<Icon size="sm" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
					</Button>

					{canReply && onReply && !isReplying && (
						<Button onClick={() => setIsReplying(true)} variant="ghost" size="sm">
							Reply
						</Button>
					)}

					<p className="ml-auto mb-0">{comment.created_at}</p>
				</div>

				{isReplying && onReply && (
					<CommentForm
						onSubmit={handleReply}
						isSubmitting={state.isReplyPending}
						submitLabel="Reply"
						placeholder="Your reply"
						onCancel={() => setIsReplying(false)}
					/>
				)}

				<hr />
			</div>
		</div>
	);
};

export default CommentItem;

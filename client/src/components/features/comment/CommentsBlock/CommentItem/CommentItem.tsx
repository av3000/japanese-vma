import React, { useState } from 'react';
import classNames from 'classnames';
import type { ApiComment, ApiCommentReply } from '@/api/comments';
import DefaultAvatar from '@/assets/images/avatar-man.svg';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Cluster } from '@/components/shared/layout';
import CommentForm from '../CommentForm/CommentForm';
import styles from './CommentItem.module.css';

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
		<li className={classNames(styles.item, isReply && styles.reply)}>
			<img className={styles.avatar} src={DefaultAvatar} alt="" width="40" height="40" />
			<div className={styles.body}>
				<Cluster justify="between">
					<h5 className={styles.author}>@{comment.author?.name ?? DELETED_AUTHOR_NAME}</h5>
					<Cluster gap="2xs">
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
					</Cluster>
				</Cluster>

				{isEditing ? (
					<CommentForm
						onSubmit={handleEdit}
						isSubmitting={state.isEditPending}
						initialValue={comment.content}
						submitLabel="Save"
						onCancel={() => setIsEditing(false)}
					/>
				) : (
					<p className={styles.content}>{comment.content}</p>
				)}

				{state.error && <Alert tone="danger">{state.error}</Alert>}

				<Cluster gap="xs" className={styles.footer}>
					<span>{comment.likes_count} likes</span>
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

					<span className={styles.date}>{comment.created_at}</span>
				</Cluster>

				{isReplying && onReply && (
					<CommentForm
						onSubmit={handleReply}
						isSubmitting={state.isReplyPending}
						submitLabel="Reply"
						placeholder="Your reply"
						onCancel={() => setIsReplying(false)}
					/>
				)}
			</div>
		</li>
	);
};

export default CommentItem;

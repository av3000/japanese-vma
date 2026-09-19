import React, { useState } from 'react';
import { useCommentRepliesQuery, type ApiComment, type ApiCommentReply } from '@/api/comments';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/ui/badge';
import CommentItem, { type CommentActionState } from '../CommentItem/CommentItem';
import styles from './CommentList.module.css';

interface CommentListProps {
	comments: ApiComment[];
	/** Thread size, from `pagination.total` - not the length of the loaded page. */
	total: number;
	hasMore: boolean;
	canReply: boolean;
	stateFor: (commentId: number) => CommentActionState;
	onLike: (commentId: number) => void;
	onDelete: (comment: ApiComment | ApiCommentReply) => void;
	onEdit: (comment: ApiComment | ApiCommentReply, content: string) => Promise<void>;
	onReply: (rootCommentId: number, content: string) => Promise<void>;
}

/**
 * One top-level comment plus its replies.
 *
 * The thread carries a bounded preview of each subtree and its full
 * `replies_count`; the rest is fetched on demand rather than inlined, so a
 * comment with hundreds of replies does not decide the page's payload size.
 */
const CommentThread: React.FC<{
	comment: ApiComment;
	canReply: boolean;
	stateFor: CommentListProps['stateFor'];
	onLike: CommentListProps['onLike'];
	onDelete: CommentListProps['onDelete'];
	onEdit: CommentListProps['onEdit'];
	onReply: CommentListProps['onReply'];
}> = ({ comment, canReply, stateFor, onLike, onDelete, onEdit, onReply }) => {
	const [showAllReplies, setShowAllReplies] = useState(false);
	const { data: allReplies, isLoading: isLoadingReplies } = useCommentRepliesQuery(comment.uuid, showAllReplies);

	const replies = showAllReplies && allReplies ? allReplies.items : comment.replies;
	const hiddenReplies = comment.replies_count - replies.length;

	return (
		<>
			<CommentItem
				comment={comment}
				canReply={canReply}
				state={stateFor(comment.id)}
				onLike={() => onLike(comment.id)}
				onDelete={() => onDelete(comment)}
				onEdit={(content) => onEdit(comment, content)}
				onReply={(content) => onReply(comment.id, content)}
			/>

			{replies.map((reply) => (
				<CommentItem
					key={reply.id}
					comment={reply}
					isReply
					canReply={canReply}
					state={stateFor(reply.id)}
					onLike={() => onLike(reply.id)}
					onDelete={() => onDelete(reply)}
					onEdit={(content) => onEdit(reply, content)}
					// A reply to a reply still belongs to this comment's subtree.
					onReply={(content) => onReply(comment.id, content)}
				/>
			))}

			{hiddenReplies > 0 && (
				<li className={styles.moreReplies}>
					<Button
						onClick={() => setShowAllReplies(true)}
						variant="ghost"
						size="sm"
						isLoading={isLoadingReplies}
					>
						Show {hiddenReplies} more {hiddenReplies === 1 ? 'reply' : 'replies'}
					</Button>
				</li>
			)}
		</>
	);
};

const CommentList: React.FC<CommentListProps> = ({
	comments,
	total,
	hasMore,
	canReply,
	stateFor,
	onLike,
	onDelete,
	onEdit,
	onReply,
}) => {
	return (
		<div>
			<h5 className={styles.heading}>
				<Badge variant="secondary">{total}</Badge>
				{'  '}
				Comment{total !== 1 ? 's' : ''}
			</h5>

			{comments.length === 0 ? (
				<Alert tone="info" className={styles.empty}>
					Be the first to comment
				</Alert>
			) : (
				<>
					<ul className={styles.items}>
						{comments.map((comment) => (
							<CommentThread
								key={comment.id}
								comment={comment}
								canReply={canReply}
								stateFor={stateFor}
								onLike={onLike}
								onDelete={onDelete}
								onEdit={onEdit}
								onReply={onReply}
							/>
						))}
					</ul>

					{hasMore && (
						<p className={styles.showing}>
							Showing {comments.length} of {total} comments
						</p>
					)}
				</>
			)}
		</div>
	);
};

export default CommentList;

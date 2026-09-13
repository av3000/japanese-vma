import React from 'react';
import { ApiComment as Comment } from '@/api/comments';
import DefaultAvatar from '@/assets/images/avatar-man.svg';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Cluster } from '@/components/shared/layout';
import styles from './CommentItem.module.css';

interface CommentItemProps {
	comment: Comment;
	// TODO: use proper User type with admin fields included
	currentUser: any;
	onDelete: () => void;
	onLike: () => void;
	isLikePending: boolean;
}

const CommentItem: React.FC<CommentItemProps> = ({ comment, currentUser, onDelete, onLike, isLikePending }) => {
	const canDelete = currentUser && (currentUser.id === comment.author_id || currentUser.is_admin);

	return (
		<li className={styles.item}>
			<img className={styles.avatar} src={DefaultAvatar} alt="default-avatar" />
			<div className={styles.body}>
				<Cluster justify="between">
					<h5 className={styles.author}>@{comment.author_name}</h5>
					{canDelete && (
						<Button onClick={onDelete} variant="ghost" size="sm" aria-label="Delete this comment">
							<Icon size="sm" name="trashbinSolid" />
						</Button>
					)}
				</Cluster>
				<p className={styles.content}>{comment.content}</p>
				<Cluster gap="xs" className={styles.footer}>
					<span>{comment.likes_count} likes</span>
					<Button
						onClick={onLike}
						variant="ghost"
						size="sm"
						aria-label={comment.is_liked_by_viewer ? 'Unlike this comment' : 'Like this comment'}
						aria-pressed={comment.is_liked_by_viewer}
						disabled={isLikePending}
					>
						<Icon size="sm" name={comment.is_liked_by_viewer ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
					</Button>
					<span className={styles.date}>{comment.created_at}</span>
				</Cluster>
			</div>
		</li>
	);
};

export default CommentItem;

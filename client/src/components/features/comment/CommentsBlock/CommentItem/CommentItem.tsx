import React from 'react';
import { ApiComment as Comment } from '@/api/comments';
import DefaultAvatar from '@/assets/images/avatar-man.svg';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface CommentItemProps {
	comment: Comment;
	// TODO: use proper User type with admin fields included
	currentUser: any;
	onDelete: () => void;
	onLike: () => void;
	isLoading: boolean;
}

const CommentItem: React.FC<CommentItemProps> = ({ comment, currentUser, onDelete, onLike, isLoading }) => {
	const canDelete = currentUser && (currentUser.id === comment.author_id || currentUser.is_admin);

	return (
		<div className="media">
			<img className="d-flex mr-3 rounder-circle" src={DefaultAvatar} alt="default-avatar" />
			<div className="media-body">
				<div className="d-flex justify-content-between align-items-center">
					<h5>@{comment.author_name}</h5>
					{canDelete && (
						<Button onClick={onDelete} variant="ghost" size="sm">
							<Icon size="sm" name="trashbinSolid" />
						</Button>
					)}
				</div>
				<div>{comment.content}</div>
				<br />
				<div className="text-muted d-flex align-items-center">
					<span className="mx-2">{comment.likes_count} likes</span>
					<Button onClick={onLike} variant="ghost" size="sm" disabled={isLoading}>
						<Icon size="sm" name={comment.is_liked_by_viewer ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
					</Button>
					<p className="ml-auto mb-0">{comment.created_at}</p>
				</div>
				<hr />
			</div>
		</div>
	);
};

export default CommentItem;

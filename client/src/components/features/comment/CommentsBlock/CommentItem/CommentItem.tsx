import React from 'react';
import DefaultAvatar from '@/assets/images/avatar-man.svg';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface Comment {
	id: string | number;
	userName: string;
	user_id: string | number;
	content: string;
	created_at: string;
	likesTotal: number;
	isLiked: boolean;
}

interface User {
	id: string | number;
	is_admin?: boolean;
}

interface CommentItemProps {
	comment: Comment;
	currentUser: User | null;
	onDelete: () => void;
	onLike: () => void;
}

const CommentItem: React.FC<CommentItemProps> = ({ comment, currentUser, onDelete, onLike }) => {
	const canDelete = currentUser && (currentUser.id === comment.user_id || currentUser.is_admin);

	return (
		<div className="media">
			<img className="d-flex mr-3 rounder-circle" src={DefaultAvatar} alt="default-avatar" />
			<div className="media-body">
				<div className="d-flex justify-content-between align-items-center">
					<h5>@{comment.userName}</h5>
					{canDelete && (
						<Button onClick={onDelete} variant="ghost" size="sm">
							<Icon size="sm" name="trashbinSolid" />
						</Button>
					)}
				</div>
				<div>{comment.content}</div>
				<br />
				<div className="text-muted d-flex align-items-center">
					<span className="mx-2">{comment.likesTotal} likes</span>
					<Button onClick={onLike} variant="ghost" size="sm">
						<Icon size="sm" name="thumbsUpSolid" />
					</Button>
					<p className="ml-auto mb-0">{comment.created_at}</p>
				</div>
				<hr />
			</div>
		</div>
	);
};

export default CommentItem;

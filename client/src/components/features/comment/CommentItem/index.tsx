import React from 'react';

import DefaultAvatar from '@/assets/images/avatar-man.svg';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface User {
  id: string | number;
  isAdmin?: boolean;
}

interface CurrentUser {
  user: User;
}

interface CommentData {
  id: string | number;
  userName: string;
  user_id: string | number;
  content: string;
  created_at: string;
  likesTotal: number;
  isLiked: boolean;
}

interface CommentProps {
  comment: CommentData;
  deleteComment: () => void;
  likeComment: () => void;
  currentUser: CurrentUser;
}

const Comment: React.FC<CommentProps> = ({ comment, deleteComment, likeComment, currentUser }) => {
  const canDelete = currentUser.user.id === comment.user_id || currentUser.user.isAdmin;

  return (
    <div className="media">
      <img className="d-flex mr-3 rounder-circle" src={DefaultAvatar} alt="default-avatar" />
      <div className="media-body">
        <div className="d-flex justify-content-between align-items-center">
          <h5>@{comment.userName}</h5>
          {canDelete && (
            <Button onClick={deleteComment} variant="ghost" size="sm">
              <Icon size="sm" name="trashbinSolid" />
            </Button>
          )}
        </div>
        <div>{comment.content}</div>
        <br />
        <div className="text-muted d-flex align-items-center">
          <span className="mx-2">{comment.likesTotal} likes</span>
          <Button onClick={likeComment} variant="ghost" size="sm">
            <Icon size="sm" name={'thumbsUpSolid'} />
          </Button>
          <p className="ml-auto mb-0">{comment.created_at}</p>
        </div>
        <hr />
      </div>
    </div>
  );
};

export default Comment;

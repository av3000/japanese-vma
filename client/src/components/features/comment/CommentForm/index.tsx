import React, { ChangeEvent, FormEvent, useState } from 'react';

import axios from 'axios';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { BASE_URL } from '@/shared/constants';

const MAX_CHAR_LIMIT = 1000;

interface User {
  id: string | number;
  name: string;
}

interface Comment {
  id: string | number;
  content: string;
  userName?: string;
  user_id?: string | number;
  created_at?: string;
  likesTotal?: number;
  isLiked?: boolean;
}

interface CurrentUser {
  isAuthenticated: boolean;
  user: User;
}

interface History {
  push: (path: string) => void;
}

interface CommentFormProps {
  currentUser: CurrentUser;
  history: History;
  objectId: string | number;
  objectType: string;
  addComment: (comment: Comment) => void;
}

const CommentForm: React.FC<CommentFormProps> = ({
  currentUser,
  history,
  objectId,
  objectType,
  addComment,
}) => {
  const [message, setMessage] = useState<string>('');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string>('');

  const handleChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
    const newMessage = e.target.value.slice(0, MAX_CHAR_LIMIT);
    setMessage(newMessage);
    setError(newMessage.trim() ? '' : 'Message is empty.');
  };

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!currentUser.isAuthenticated) {
      history.push('/login');
      return;
    }

    if (!message.trim()) {
      setError('Message is empty.');
      return;
    }

    setIsLoading(true);
    const url = `${BASE_URL}/api/${objectType}/${objectId}/comment`;

    try {
      const res = await axios.post(url, { content: message });
      const newComment = res.data.comment as Comment;
      newComment.userName = currentUser.user.name;
      addComment(newComment);
      setMessage('');
    } catch (err) {
      console.error('Error adding comment:', err);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={onSubmit}>
      <div className="form-group">
        <textarea
          onChange={handleChange}
          value={message}
          className="form-control"
          placeholder="Your Comment"
          name="message"
          rows={5}
          maxLength={MAX_CHAR_LIMIT}
        />
        <small className="form-text text-muted">
          {MAX_CHAR_LIMIT - message.length} characters remaining
        </small>
      </div>
      {error && <div className="alert alert-danger">{error}</div>}
      <div className="form-group">
        <Button
          type="submit"
          disabled={isLoading || !message.trim()}
          variant="outline"
          isLoading={isLoading}
          size="sm"
        >
          Comment
          <Icon name="paperPlane" size="sm" />
        </Button>
      </div>
    </form>
  );
};

export default CommentForm;

import React, { ChangeEvent, FormEvent, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

const MAX_CHAR_LIMIT = 1000;

interface CommentFormProps {
	onSubmit: (content: string) => Promise<void>;
	isSubmitting: boolean;
}

const CommentForm: React.FC<CommentFormProps> = ({ onSubmit, isSubmitting }) => {
	const [message, setMessage] = useState<string>('');
	const [error, setError] = useState<string>('');

	const handleChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
		const newMessage = e.target.value.slice(0, MAX_CHAR_LIMIT);
		setMessage(newMessage);
		setError(newMessage.trim() ? '' : 'Message is empty.');
	};

	const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();

		if (!message.trim()) {
			setError('Message is empty.');
			return;
		}

		try {
			await onSubmit(message);
			setMessage('');
			setError('');
		} catch (err) {
			setError('Failed to post comment. Please try again.');
		}
	};

	return (
		<form onSubmit={handleSubmit}>
			<div className="form-group">
				<textarea
					onChange={handleChange}
					value={message}
					className="form-control"
					placeholder="Your Comment"
					name="message"
					rows={5}
					maxLength={MAX_CHAR_LIMIT}
					disabled={isSubmitting}
				/>
				<small className="form-text text-muted">{MAX_CHAR_LIMIT - message.length} characters remaining</small>
			</div>

			{error && <div className="alert alert-danger">{error}</div>}

			<div className="form-group">
				<Button
					type="submit"
					disabled={isSubmitting || !message.trim()}
					variant="outline"
					isLoading={isSubmitting}
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

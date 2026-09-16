import React, { ChangeEvent, FormEvent, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

const MAX_CHAR_LIMIT = 1000;

/** Mirrors `StoreCommentRequest.content` / `UpdateCommentRequest.content`. */
const MIN_CHAR_LIMIT = 2;

interface CommentFormProps {
	onSubmit: (content: string) => Promise<void>;
	/**
	 * The submitting mutation's pending flag - not the thread query's loading
	 * flag. Wiring the query here left the button live for the whole duration of
	 * a post, and disabled it during the initial read when there was nothing to
	 * wait for.
	 */
	isSubmitting: boolean;
	initialValue?: string;
	submitLabel?: string;
	placeholder?: string;
	onCancel?: () => void;
}

const CommentForm: React.FC<CommentFormProps> = ({
	onSubmit,
	isSubmitting,
	initialValue = '',
	submitLabel = 'Comment',
	placeholder = 'Your Comment',
	onCancel,
}) => {
	const [message, setMessage] = useState<string>(initialValue);
	const [error, setError] = useState<string>('');

	const isTooShort = message.trim().length < MIN_CHAR_LIMIT;

	const handleChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
		const newMessage = e.target.value.slice(0, MAX_CHAR_LIMIT);
		setMessage(newMessage);
		setError('');
	};

	const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();

		// Checked here as well as by the disabled button so the contract's minimum
		// fails locally instead of round-tripping to a 422.
		if (isTooShort) {
			setError(`Comment must be at least ${MIN_CHAR_LIMIT} characters.`);
			return;
		}

		try {
			await onSubmit(message.trim());
			setMessage(initialValue);
			setError('');
		} catch {
			setError('Failed to save comment. Please try again.');
		}
	};

	return (
		<form onSubmit={handleSubmit}>
			<div className="form-group">
				<textarea
					onChange={handleChange}
					value={message}
					className="form-control"
					placeholder={placeholder}
					name="message"
					rows={5}
					maxLength={MAX_CHAR_LIMIT}
					disabled={isSubmitting}
				/>
				<small className="form-text text-muted">{MAX_CHAR_LIMIT - message.length} characters remaining</small>
			</div>

			{error && <div className="alert alert-danger">{error}</div>}

			<div className="form-group d-flex align-items-center">
				<Button type="submit" disabled={isSubmitting || isTooShort} variant="outline" isLoading={isSubmitting} size="sm">
					{submitLabel}
					<Icon name="paperPlane" size="sm" />
				</Button>

				{onCancel && (
					<Button type="button" onClick={onCancel} variant="ghost" size="sm" disabled={isSubmitting}>
						Cancel
					</Button>
				)}
			</div>
		</form>
	);
};

export default CommentForm;

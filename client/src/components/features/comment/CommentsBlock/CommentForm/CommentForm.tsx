import React, { ChangeEvent, FormEvent, useState } from 'react';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Textarea } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import { Cluster, Stack } from '@/components/shared/layout';

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
		<Stack as="form" gap="sm" onSubmit={handleSubmit}>
			<Field>
				<Textarea
					onChange={handleChange}
					value={message}
					placeholder={placeholder}
					aria-label={placeholder}
					name="message"
					rows={5}
					maxLength={MAX_CHAR_LIMIT}
					disabled={isSubmitting}
					isInvalid={!!error}
				/>
				<FieldMessage alignEnd>{MAX_CHAR_LIMIT - message.length} characters remaining</FieldMessage>
			</Field>

			{error && <Alert tone="danger">{error}</Alert>}

			<Cluster gap="xs">
				<Button
					type="submit"
					disabled={isSubmitting || isTooShort}
					variant="outline"
					isLoading={isSubmitting}
					size="sm"
				>
					{submitLabel}
					<Icon name="paperPlane" size="sm" />
				</Button>

				{onCancel && (
					<Button type="button" onClick={onCancel} variant="ghost" size="sm" disabled={isSubmitting}>
						Cancel
					</Button>
				)}
			</Cluster>
		</Stack>
	);
};

export default CommentForm;

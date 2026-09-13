import React, { ChangeEvent, FormEvent, useState } from 'react';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Textarea } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import { Stack } from '@/components/shared/layout';

const MAX_CHAR_LIMIT = 1000;

interface CommentFormProps {
	onSubmit: (content: string) => Promise<void>;
	isLoading: boolean;
}

const CommentForm: React.FC<CommentFormProps> = ({ onSubmit, isLoading }) => {
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
			console.error(err);
			setError('Failed to post comment. Please try again.');
		}
	};

	return (
		<Stack as="form" gap="sm" onSubmit={handleSubmit}>
			<Field>
				<Textarea
					onChange={handleChange}
					value={message}
					placeholder="Your Comment"
					aria-label="Your Comment"
					name="message"
					rows={5}
					maxLength={MAX_CHAR_LIMIT}
					disabled={isLoading}
				/>
				<FieldMessage>{MAX_CHAR_LIMIT - message.length} characters remaining</FieldMessage>
			</Field>

			{error && <Alert tone="danger">{error}</Alert>}

			<div>
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
		</Stack>
	);
};

export default CommentForm;

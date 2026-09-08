import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/shared/Button';
import {
	MAX_SENTENCE_LENGTH,
	sentenceFormSchema,
	type SentenceFormValues,
} from './sentenceFormSchema';

export type { SentenceFormValues } from './sentenceFormSchema';

interface SentenceFormProps {
	initialValues: SentenceFormValues;
	onSubmit: (values: SentenceFormValues) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	serverErrors?: Record<string, string[]> | null;
	statusMessage?: string | null;
	disableSubmitWhenUnchanged?: boolean;
}

export function SentenceForm({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	serverErrors,
	statusMessage,
	disableSubmitWhenUnchanged = false,
}: SentenceFormProps) {
	const [isFocused, setIsFocused] = useState(false);

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		clearErrors,
		formState: { errors, touchedFields, isDirty, isValid },
	} = useForm<SentenceFormValues>({
		defaultValues: initialValues,
		mode: 'onChange',
		resolver: zodResolver(sentenceFormSchema),
	});

	const contentValue = useWatch({ control, name: 'content' }) ?? '';

	useEffect(() => {
		reset(initialValues);
	}, [initialValues, reset]);

	useEffect(() => {
		if (!serverErrors) {
			return;
		}

		let generalError: string | null = null;

		for (const [rawField, messages] of Object.entries(serverErrors)) {
			const message = messages?.[0];
			if (!message) continue;

			if (rawField.split('.')[0] === 'content') {
				setError('content', { type: 'server', message });
				continue;
			}

			generalError ??= message;
		}

		if (generalError) {
			setError('root' as never, { type: 'server', message: generalError });
		}
	}, [serverErrors, setError]);

	// Suppress the field error while the field has focus so it does not flash on
	// every keystroke; a server error is always shown. Same rule as ArticleForm.
	const visibleContentError = (() => {
		const error = errors.content;
		if (!error || isFocused) return undefined;

		return error.type === 'server' || touchedFields.content ? error.message : undefined;
	})();

	const generalErrorMessage = (errors as { root?: { message?: string } })?.root?.message;

	const contentField = register('content', {
		onChange: () => clearErrors(['content', 'root'] as never),
	});

	return (
		<form onSubmit={handleSubmit(onSubmit)} className="col-12">
			<h4>Sentence</h4>
			<textarea
				className="form-control resize-none"
				rows={4}
				maxLength={MAX_SENTENCE_LENGTH}
				aria-label="Sentence"
				{...contentField}
				onFocus={() => setIsFocused(true)}
				onBlur={(event) => {
					contentField.onBlur(event);
					setIsFocused(false);
				}}
				required
			/>
			<small
				className={`d-block text-end ${
					contentValue.length >= MAX_SENTENCE_LENGTH ? 'text-danger' : 'text-muted'
				}`}
			>
				{contentValue.length}/{MAX_SENTENCE_LENGTH}
			</small>
			{visibleContentError && <div className="text-danger">{visibleContentError}</div>}

			<div className="mt-4">
				<Button
					type="submit"
					variant="outline"
					disabled={isSubmitting || (disableSubmitWhenUnchanged && !isDirty) || !isValid}
				>
					{isSubmitting ? (
						<span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
					) : (
						submitLabel
					)}
				</Button>
			</div>

			{statusMessage && <div className="text-danger mt-3">{statusMessage}</div>}
			{generalErrorMessage && <div className="text-danger mt-3">{generalErrorMessage}</div>}
		</form>
	);
}

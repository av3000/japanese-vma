import { useEffect, useState, type ReactNode } from 'react';
import { useForm, useWatch, type FieldPath } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { ApiError } from '@/api/apiError';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormField, Textarea } from '@/components/shared/FormControls';
import { FormCard, FormLayout } from '@/components/shared/FormPage';
import { getFieldErrorMessages } from '@/helpers/formErrors';
import { NO_CHANGES_MESSAGE, useApiErrorInForm } from '@/hooks/useApiErrorInForm';
import {
	MAX_SENTENCE_LENGTH,
	MIN_SENTENCE_LENGTH,
	sentenceFormSchema,
	type SentenceFormValues,
} from './sentenceFormSchema';

export type { SentenceFormValues } from './sentenceFormSchema';

const SENTENCE_FORM_FIELDS: readonly FieldPath<SentenceFormValues>[] = ['content'];

interface SentenceFormProps {
	initialValues: SentenceFormValues;
	onSubmit: (values: SentenceFormValues) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	/** The last rejected save, from `parseApiError`. */
	apiError?: ApiError | null;
	/** Edit forms: an unchanged form says so instead of sending the same text again. */
	requireChanges?: boolean;
	/** The Cancel control. */
	cancel: ReactNode;
}

export function SentenceForm({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	apiError,
	requireChanges = false,
	cancel,
}: SentenceFormProps) {
	const [showNoChanges, setShowNoChanges] = useState(false);

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		formState: { errors, isDirty },
	} = useForm<SentenceFormValues>({
		defaultValues: initialValues,
		mode: 'onTouched',
		resolver: zodResolver(sentenceFormSchema),
	});

	const content = useWatch({ control, name: 'content' }) ?? '';

	useEffect(() => {
		reset(initialValues);
	}, [initialValues, reset]);

	useEffect(() => {
		if (isDirty) setShowNoChanges(false);
	}, [isDirty]);

	const alertMessage = useApiErrorInForm(apiError, setError, SENTENCE_FORM_FIELDS);

	const onValidSubmit = (values: SentenceFormValues) => {
		if (requireChanges && !isDirty) {
			setShowNoChanges(true);
			return;
		}

		setShowNoChanges(false);
		onSubmit(values);
	};

	const alert = showNoChanges ? (
		<Alert tone="info">{NO_CHANGES_MESSAGE}</Alert>
	) : alertMessage ? (
		<Alert tone="danger">{alertMessage}</Alert>
	) : null;

	return (
		<form onSubmit={handleSubmit(onValidSubmit)} noValidate>
			<FormLayout
				alert={alert}
				actions={
					<>
						<Button type="submit" variant="primary" isLoading={isSubmitting}>
							{submitLabel}
						</Button>
						{cancel}
					</>
				}
			>
				<FormCard>
					<FormField
						label="Sentence"
						hint={`Between ${MIN_SENTENCE_LENGTH} and ${MAX_SENTENCE_LENGTH} characters.`}
						counter={`${content.length} / ${MAX_SENTENCE_LENGTH}`}
						error={getFieldErrorMessages(errors.content)}
					>
						{(fieldControl) => (
							<Textarea
								lang="ja"
								rows={4}
								maxLength={MAX_SENTENCE_LENGTH}
								required
								{...fieldControl}
								{...register('content')}
							/>
						)}
					</FormField>
				</FormCard>
			</FormLayout>
		</form>
	);
}

import { useEffect, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch, type FieldPath } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { POST_TOPIC_OPTIONS, isPostTopic } from '@/api/posts/reads';
import type { WriteFailure } from '@/api/writeFailure';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormField, Input, Select, Textarea } from '@/components/shared/FormControls';
import { FormCard, FormLayout, FormNote } from '@/components/shared/FormPage';
import { InputTags } from '@/components/shared/InputTags';
import { fieldErrorMessages } from '@/helpers/applyServerFieldErrors';
import { NO_CHANGES_MESSAGE, useWriteFailureAlert } from '@/hooks/useWriteFailureAlert';
import {
	MAX_CONTENT_LENGTH,
	MAX_TAG_LENGTH,
	MAX_TAG_QUANTITY,
	MAX_TITLE_LENGTH,
	POST_FORM_FIELDS,
	postFormSchema,
	type PostFormField,
	type PostFormValues,
} from './postFormSchema';

export type { PostFormValues, PostFormField } from './postFormSchema';

export type PostFormSubmitMeta = { dirtyKeys: PostFormField[] };

/**
 * The server names the persisted relation `hashtags` and still accepts the legacy `type` field
 * name, neither of which is a form field. Mapping them here keeps a 422 attached to the control
 * that caused it instead of surfacing as an anonymous banner.
 */
const SERVER_FIELD_MAP: Partial<Record<string, FieldPath<PostFormValues>>> = {
	hashtags: 'tags',
	type: 'topic',
};

interface PostFormProps {
	initialValues: PostFormValues;
	onSubmit: (values: PostFormValues, meta: PostFormSubmitMeta) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	/** The last rejected save, from the route's write reader. */
	failure?: WriteFailure | null;
	/** Edit forms: an unchanged form says so instead of sending an empty update. */
	requireChanges?: boolean;
	/** The Cancel control. */
	cancel: ReactNode;
}

/**
 * The one Post authoring surface, shared by create and edit. Both routes used to carry their own
 * copy of this markup, which is how the topic vocabulary and the length rules drifted apart from
 * the contract in the first place.
 */
export function PostForm({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	failure,
	requireChanges = false,
	cancel,
}: PostFormProps) {
	const [showNoChanges, setShowNoChanges] = useState(false);

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		formState: { errors, dirtyFields, isDirty },
	} = useForm<PostFormValues>({
		defaultValues: initialValues,
		mode: 'onTouched',
		resolver: zodResolver(postFormSchema),
	});

	const [title, content] = useWatch({ control, name: ['title', 'content'] });

	useEffect(() => {
		reset(initialValues);
	}, [initialValues, reset]);

	useEffect(() => {
		if (isDirty) setShowNoChanges(false);
	}, [isDirty]);

	const failureMessage = useWriteFailureAlert(failure, setError, POST_FORM_FIELDS, SERVER_FIELD_MAP);

	const onValidSubmit = (values: PostFormValues) => {
		// `UpdatePostRequest` rejects an empty body, so an unchanged edit never reaches the server.
		if (requireChanges && !isDirty) {
			setShowNoChanges(true);
			return;
		}

		setShowNoChanges(false);
		onSubmit(values, { dirtyKeys: Object.keys(dirtyFields) as PostFormField[] });
	};

	const alert = showNoChanges ? (
		<Alert tone="info">{NO_CHANGES_MESSAGE}</Alert>
	) : failureMessage ? (
		<Alert tone="danger">{failureMessage}</Alert>
	) : null;

	return (
		<form onSubmit={handleSubmit(onValidSubmit)} noValidate>
			<FormLayout
				alert={alert}
				aside={
					<>
						<FormCard title="Settings">
							<FormField label="Topic" error={fieldErrorMessages(errors.topic)}>
								{(fieldControl) => (
									<Controller
										control={control}
										name="topic"
										render={({ field }) => (
											<Select
												ref={field.ref}
												{...fieldControl}
												name={field.name}
												value={String(field.value)}
												onChange={(event) => {
													const next = Number(event.target.value);
													// A non-topic value can only come from a tampered DOM; the schema rejects it either way.
													field.onChange(isPostTopic(next) ? next : event.target.value);
												}}
												onBlur={field.onBlur}
											>
												{POST_TOPIC_OPTIONS.map((option) => (
													<option key={option.value} value={String(option.value)}>
														{option.label}
													</option>
												))}
											</Select>
										)}
									/>
								)}
							</FormField>

							<FormField
								label="Tags"
								hint={`Up to ${MAX_TAG_QUANTITY} tags, ${MAX_TAG_LENGTH} characters each. Press Enter, comma or space to add one.`}
								error={fieldErrorMessages(errors.tags)}
							>
								{(fieldControl) => (
									<Controller
										control={control}
										name="tags"
										render={({ field }) => (
											<InputTags
												ref={field.ref}
												id={fieldControl.id}
												aria-describedby={fieldControl['aria-describedby']}
												isInvalid={fieldControl.isInvalid}
												value={field.value}
												onChange={field.onChange}
												onBlur={field.onBlur}
												maxTags={MAX_TAG_QUANTITY}
												maxTagLength={MAX_TAG_LENGTH}
											/>
										)}
									/>
								)}
							</FormField>
						</FormCard>
						<FormNote title="Before you post">
							<p>Be kind. Posts can be locked by moderators.</p>
						</FormNote>
					</>
				}
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
						label="Title"
						counter={`${(title ?? '').length} / ${MAX_TITLE_LENGTH}`}
						error={fieldErrorMessages(errors.title)}
					>
						{(fieldControl) => (
							<Input maxLength={MAX_TITLE_LENGTH} required {...fieldControl} {...register('title')} />
						)}
					</FormField>

					<FormField
						label="Text"
						counter={`${(content ?? '').length} / ${MAX_CONTENT_LENGTH}`}
						error={fieldErrorMessages(errors.content)}
					>
						{(fieldControl) => (
							<Textarea
								rows={12}
								maxLength={MAX_CONTENT_LENGTH}
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

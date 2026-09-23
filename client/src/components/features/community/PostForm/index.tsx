import { useEffect, useId, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { POST_TOPIC_OPTIONS, isPostTopic } from '@/api/posts/reads';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Input, Label, Select, Textarea } from '@/components/shared/FormControls';
import { InputTags } from '@/components/shared/InputTags';
import Spinner from '@/components/shared/Spinner';
import { Stack } from '@/components/shared/layout';
import {
	MAX_CONTENT_LENGTH,
	MAX_TAG_LENGTH,
	MAX_TAG_QUANTITY,
	MAX_TITLE_LENGTH,
	isPostFormField,
	postFormSchema,
	type PostFormField,
	type PostFormValues,
} from './postFormSchema';

export type { PostFormValues, PostFormField } from './postFormSchema';

export type PostFormSubmitMeta = { dirtyKeys: PostFormField[] };

interface PostFormProps {
	initialValues: PostFormValues;
	onSubmit: (values: PostFormValues, meta: PostFormSubmitMeta) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	serverErrors?: Record<string, string[]> | null;
	statusMessage?: string | null;
	disableSubmitWhenUnchanged?: boolean;
}

/**
 * The server names the persisted relation `hashtags` and still accepts the legacy `type` field
 * name, neither of which is a form field. Mapping them here keeps a 422 attached to the control
 * that caused it instead of surfacing as an anonymous banner.
 */
const serverFieldToFormField: Partial<Record<string, PostFormField>> = {
	hashtags: 'tags',
	type: 'topic',
};

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
	serverErrors,
	statusMessage,
	disableSubmitWhenUnchanged = false,
}: PostFormProps) {
	const [focusedField, setFocusedField] = useState<PostFormField | null>(null);

	const idPrefix = useId();
	const titleId = `${idPrefix}-title`;
	const contentId = `${idPrefix}-content`;
	const topicId = `${idPrefix}-topic`;
	const tagsLabelId = `${idPrefix}-tags-label`;

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		clearErrors,
		formState: { errors, touchedFields, dirtyFields, isDirty, isValid },
	} = useForm<PostFormValues>({
		defaultValues: initialValues,
		mode: 'onChange',
		resolver: zodResolver(postFormSchema),
	});

	const titleValue = useWatch({ control, name: 'title' }) ?? '';
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

			// `tags.3` reports against the tags control as a whole.
			const baseField = rawField.split('.')[0];
			const mappedField = serverFieldToFormField[baseField] ?? baseField;

			if (isPostFormField(mappedField)) {
				setError(mappedField, { type: 'server', message });
				continue;
			}

			generalError ??= message;
		}

		if (generalError) {
			setError('root' as never, { type: 'server', message: generalError });
		}
	}, [serverErrors, setError]);

	const clearFieldAndRootErrors = (field: PostFormField) => {
		clearErrors([field, 'root'] as never);
	};

	// Suppress a field error while the field has focus so it does not flash on every keystroke; a
	// server error is always shown. Same rule as ArticleForm and SentenceForm.
	const getVisibleFieldError = (field: PostFormField) => {
		const error = errors[field];
		if (!error || focusedField === field) return undefined;

		const wasTouched = Boolean((touchedFields as Partial<Record<PostFormField, unknown>>)[field]);

		return error.type === 'server' || wasTouched ? error.message : undefined;
	};

	const generalErrorMessage = (errors as { root?: { message?: string } })?.root?.message;

	const onValidSubmit = (values: PostFormValues) => {
		onSubmit(values, { dirtyKeys: Object.keys(dirtyFields) as PostFormField[] });
	};

	const titleError = getVisibleFieldError('title');
	const contentError = getVisibleFieldError('content');
	const topicError = getVisibleFieldError('topic');
	const tagsError = getVisibleFieldError('tags');

	const titleField = register('title', { onChange: () => clearFieldAndRootErrors('title') });
	const contentField = register('content', { onChange: () => clearFieldAndRootErrors('content') });

	return (
		<Stack as="form" gap="md" onSubmit={handleSubmit(onValidSubmit)}>
			<Field>
				<Label htmlFor={titleId}>Title</Label>
				<Input
					id={titleId}
					placeholder="Post title text"
					maxLength={MAX_TITLE_LENGTH}
					isInvalid={Boolean(titleError)}
					{...titleField}
					onFocus={() => setFocusedField('title')}
					onBlur={(event) => {
						titleField.onBlur(event);
						setFocusedField(null);
					}}
					required
				/>
				<FieldMessage tone={titleValue.length >= MAX_TITLE_LENGTH ? 'error' : 'hint'} alignEnd>
					{titleValue.length}/{MAX_TITLE_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{titleError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={contentId}>Content</Label>
				<Textarea
					id={contentId}
					noResize
					placeholder="Post body text"
					rows={7}
					maxLength={MAX_CONTENT_LENGTH}
					isInvalid={Boolean(contentError)}
					{...contentField}
					onFocus={() => setFocusedField('content')}
					onBlur={(event) => {
						contentField.onBlur(event);
						setFocusedField(null);
					}}
					required
				/>
				<FieldMessage tone={contentValue.length >= MAX_CONTENT_LENGTH ? 'error' : 'hint'} alignEnd>
					{contentValue.length}/{MAX_CONTENT_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{contentError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={topicId}>Topic</Label>
				<Controller
					control={control}
					name="topic"
					render={({ field }) => (
						<Select
							id={topicId}
							isInvalid={Boolean(topicError)}
							value={String(field.value)}
							onFocus={() => setFocusedField('topic')}
							onChange={(event) => {
								clearFieldAndRootErrors('topic');
								const next = Number(event.target.value);
								// A non-topic value can only come from a tampered DOM; the schema rejects it either way.
								field.onChange(isPostTopic(next) ? next : event.target.value);
							}}
							onBlur={() => {
								field.onBlur();
								setFocusedField(null);
							}}
						>
							{POST_TOPIC_OPTIONS.map((option) => (
								<option key={option.value} value={String(option.value)}>
									{option.label}
								</option>
							))}
						</Select>
					)}
				/>
				<FieldMessage tone="error">{topicError}</FieldMessage>
			</Field>

			<Field>
				<Label id={tagsLabelId}>Tags</Label>
				<div onFocus={() => setFocusedField('tags')} onBlur={() => setFocusedField(null)}>
					<Controller
						control={control}
						name="tags"
						render={({ field }) => (
							<InputTags
								value={field.value}
								onChange={(nextTags) => {
									clearFieldAndRootErrors('tags');
									field.onChange(nextTags);
									field.onBlur();
								}}
								placeholder="uimistake suggestion howto"
								hideLabel
								label="Tags"
								aria-labelledby={tagsLabelId}
								maxTags={MAX_TAG_QUANTITY}
								maxTagLength={MAX_TAG_LENGTH}
								showTagLengthCounter
							/>
						)}
					/>
				</div>
				<FieldMessage tone="error">{tagsError}</FieldMessage>
			</Field>

			<div>
				<Button
					type="submit"
					variant="outline"
					disabled={isSubmitting || (disableSubmitWhenUnchanged && !isDirty) || !isValid}
				>
					{isSubmitting ? <Spinner size="sm" /> : submitLabel}
				</Button>
			</div>

			<FieldMessage tone="error" role="alert">
				{statusMessage}
			</FieldMessage>
			<FieldMessage tone="error" role="alert">
				{generalErrorMessage}
			</FieldMessage>
		</Stack>
	);
}

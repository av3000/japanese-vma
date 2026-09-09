import { useEffect, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { POST_TOPIC_OPTIONS, isPostTopic } from '@/api/posts/reads';
import { Button } from '@/components/shared/Button';
import { InputTags } from '@/components/shared/InputTags';
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
		<form onSubmit={handleSubmit(onValidSubmit)} className="col-12">
			<h4 className="mt-3">Title</h4>
			<input
				className="form-control"
				placeholder="Post title text"
				maxLength={MAX_TITLE_LENGTH}
				aria-label="Title"
				{...titleField}
				onFocus={() => setFocusedField('title')}
				onBlur={(event) => {
					titleField.onBlur(event);
					setFocusedField(null);
				}}
				required
			/>
			<small
				className={`d-block text-end ${titleValue.length >= MAX_TITLE_LENGTH ? 'text-danger' : 'text-muted'}`}
			>
				{titleValue.length}/{MAX_TITLE_LENGTH}
			</small>
			{titleError && <div className="text-danger">{titleError}</div>}

			<h4 className="mt-3">Content</h4>
			<textarea
				className="form-control resize-none"
				placeholder="Post body text"
				rows={7}
				maxLength={MAX_CONTENT_LENGTH}
				aria-label="Content"
				{...contentField}
				onFocus={() => setFocusedField('content')}
				onBlur={(event) => {
					contentField.onBlur(event);
					setFocusedField(null);
				}}
				required
			/>
			<small
				className={`d-block text-end ${contentValue.length >= MAX_CONTENT_LENGTH ? 'text-danger' : 'text-muted'}`}
			>
				{contentValue.length}/{MAX_CONTENT_LENGTH}
			</small>
			{contentError && <div className="text-danger">{contentError}</div>}

			<h4 className="mt-3">Topic</h4>
			<Controller
				control={control}
				name="topic"
				render={({ field }) => (
					<select
						className="form-control"
						aria-label="Topic"
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
					</select>
				)}
			/>
			{topicError && <div className="text-danger">{topicError}</div>}

			<h4 className="mt-3">Tags</h4>
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
							maxTags={MAX_TAG_QUANTITY}
							maxTagLength={MAX_TAG_LENGTH}
							showTagLengthCounter
						/>
					)}
				/>
			</div>
			{tagsError && <div className="text-danger">{tagsError}</div>}

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

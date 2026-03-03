import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/shared/Button';
import { InputTags } from '@/components/shared/InputTags';
import {
	buildArticleFormSchema,
	MAX_CONTENT_LENGTH,
	MAX_SOURCE_LINK_LENGTH,
	MAX_TAG_LENGTH,
	MAX_TAG_QUANTITY,
	MAX_TITLE_LENGTH,
	type ArticleFormValues,
} from './articleFormSchema';

export type { ArticleFormValues } from './articleFormSchema';

type ArticleFormField = Extract<keyof ArticleFormValues, string>;

export type ArticleFormSubmitMeta = { dirtyKeys: ArticleFormField[] };

interface ArticleFormProps {
	initialValues: ArticleFormValues;
	onSubmit: (values: ArticleFormValues, meta: ArticleFormSubmitMeta) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	serverErrors?: Record<string, string[]> | null;
	statusMessage?: string | null;
	requireTitleContent?: boolean;
	requireSourceLink?: boolean;
	requireEnglishTitle?: boolean;
	disableSubmitWhenUnchanged?: boolean;
}

const serverFieldToFormField: Partial<Record<string, ArticleFormField>> = {
	hashtags: 'tags',
};

const isArticleFormField = (field: string): field is ArticleFormField => {
	return (
		field === 'title_jp' ||
		field === 'title_en' ||
		field === 'content_jp' ||
		field === 'content_en' ||
		field === 'source_link' ||
		field === 'publicity' ||
		field === 'tags'
	);
};

export function ArticleForm({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	serverErrors,
	statusMessage,
	requireTitleContent = false,
	requireSourceLink = false,
	requireEnglishTitle = false,
	disableSubmitWhenUnchanged = false,
}: ArticleFormProps) {
	const schema = useMemo(() => buildArticleFormSchema({ requireEnglishTitle }), [requireEnglishTitle]);

	const [focusedField, setFocusedField] = useState<ArticleFormField | null>(null);

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		clearErrors,
		formState: { errors, touchedFields, dirtyFields, isDirty, isValid },
	} = useForm<ArticleFormValues>({
		defaultValues: initialValues,
		mode: 'onChange',
		resolver: zodResolver(schema),
	});

	const titleJpValue = useWatch({ control, name: 'title_jp' }) ?? '';
	const titleEnValue = useWatch({ control, name: 'title_en' }) ?? '';
	const contentJpValue = useWatch({ control, name: 'content_jp' }) ?? '';
	const contentEnValue = useWatch({ control, name: 'content_en' }) ?? '';
	const sourceLinkValue = useWatch({ control, name: 'source_link' }) ?? '';

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

			const baseField = rawField.split('.')[0];
			const mappedField = serverFieldToFormField[baseField] ?? baseField;

			if (isArticleFormField(mappedField)) {
				setError(mappedField, { type: 'server', message });
				continue;
			}

			generalError ??= message;
		}

		if (generalError) {
			setError('root' as any, { type: 'server', message: generalError });
		}
	}, [serverErrors, setError]);

	const clearFieldAndRootErrors = (field: ArticleFormField) => {
		clearErrors([field, 'root'] as any);
	};

	const getVisibleFieldError = (field: ArticleFormField) => {
		const error = errors[field];
		if (!error) return undefined;

		if (focusedField === field) {
			return undefined;
		}

		const wasTouched = Boolean((touchedFields as Partial<Record<ArticleFormField, unknown>>)[field]);
		if (error.type === 'server' || wasTouched) {
			return error.message;
		}

		return undefined;
	};

	const generalErrorMessage = (errors as any)?.root?.message as string | undefined;

	const onValidSubmit = (values: ArticleFormValues) => {
		const dirtyKeys = Object.keys(dirtyFields) as ArticleFormField[];
		onSubmit(values, { dirtyKeys });
	};

	const titleJpError = getVisibleFieldError('title_jp');
	const titleEnError = getVisibleFieldError('title_en');
	const contentJpError = getVisibleFieldError('content_jp');
	const contentEnError = getVisibleFieldError('content_en');
	const sourceLinkError = getVisibleFieldError('source_link');
	const tagsError = getVisibleFieldError('tags');
	const publicityError = getVisibleFieldError('publicity');

	const titleJpField = register('title_jp', { onChange: () => clearFieldAndRootErrors('title_jp') });
	const titleEnField = register('title_en', { onChange: () => clearFieldAndRootErrors('title_en') });
	const contentJpField = register('content_jp', { onChange: () => clearFieldAndRootErrors('content_jp') });
	const contentEnField = register('content_en', { onChange: () => clearFieldAndRootErrors('content_en') });
	const sourceLinkField = register('source_link', { onChange: () => clearFieldAndRootErrors('source_link') });

	return (
		<form onSubmit={handleSubmit(onValidSubmit)} className="col-12">
			<h4>Title (JP)</h4>
			<input
				className="form-control"
				maxLength={MAX_TITLE_LENGTH}
				{...titleJpField}
				onFocus={() => setFocusedField('title_jp')}
				onBlur={(e) => {
					titleJpField.onBlur(e);
					setFocusedField(null);
				}}
				required={requireTitleContent}
			/>
			<small
				className={`d-block text-end ${titleJpValue.length >= MAX_TITLE_LENGTH ? 'text-danger' : 'text-muted'}`}
			>
				{titleJpValue.length}/{MAX_TITLE_LENGTH}
			</small>
			{titleJpError && <div className="text-danger">{titleJpError}</div>}

			<h4 className="mt-3">Title (EN)</h4>
			<input
				className="form-control"
				maxLength={MAX_TITLE_LENGTH}
				{...titleEnField}
				onFocus={() => setFocusedField('title_en')}
				onBlur={(e) => {
					titleEnField.onBlur(e);
					setFocusedField(null);
				}}
				required={requireEnglishTitle}
			/>
			<small
				className={`d-block text-end ${titleEnValue.length >= MAX_TITLE_LENGTH ? 'text-danger' : 'text-muted'}`}
			>
				{titleEnValue.length}/{MAX_TITLE_LENGTH}
			</small>
			{titleEnError && <div className="text-danger">{titleEnError}</div>}

			<h4 className="mt-3">Content (JP)</h4>
			<textarea
				className="form-control resize-none"
				rows={7}
				maxLength={MAX_CONTENT_LENGTH}
				{...contentJpField}
				onFocus={() => setFocusedField('content_jp')}
				onBlur={(e) => {
					contentJpField.onBlur(e);
					setFocusedField(null);
				}}
				required={requireTitleContent}
			/>
			<small
				className={`d-block text-end ${
					contentJpValue.length >= MAX_CONTENT_LENGTH ? 'text-danger' : 'text-muted'
				}`}
			>
				{contentJpValue.length}/{MAX_CONTENT_LENGTH}
			</small>
			{contentJpError && <div className="text-danger">{contentJpError}</div>}

			<h4 className="mt-3">Content (EN)</h4>
			<textarea
				className="form-control resize-none"
				rows={5}
				maxLength={MAX_CONTENT_LENGTH}
				{...contentEnField}
				onFocus={() => setFocusedField('content_en')}
				onBlur={(e) => {
					contentEnField.onBlur(e);
					setFocusedField(null);
				}}
			/>
			<small
				className={`d-block text-end ${
					contentEnValue.length >= MAX_CONTENT_LENGTH ? 'text-danger' : 'text-muted'
				}`}
			>
				{contentEnValue.length}/{MAX_CONTENT_LENGTH}
			</small>
			{contentEnError && <div className="text-danger">{contentEnError}</div>}

			<h4 className="mt-3">Source Link</h4>
			<input
				className="form-control"
				placeholder="https://www3.nhk.or.jp/news/easy/..."
				maxLength={MAX_SOURCE_LINK_LENGTH}
				{...sourceLinkField}
				onFocus={() => setFocusedField('source_link')}
				onBlur={(e) => {
					sourceLinkField.onBlur(e);
					setFocusedField(null);
				}}
				required={requireSourceLink}
			/>
			<small
				className={`d-block text-end ${
					sourceLinkValue.length >= MAX_SOURCE_LINK_LENGTH ? 'text-danger' : 'text-muted'
				}`}
			>
				{sourceLinkValue.length}/{MAX_SOURCE_LINK_LENGTH}
			</small>
			{sourceLinkError && <div className="text-danger">{sourceLinkError}</div>}

			<h4 className="mt-3">Tags</h4>
			<div onFocus={() => setFocusedField('tags')} onBlur={() => setFocusedField(null)}>
				<Controller
					control={control}
					name="tags"
					render={({ field }) => (
						<InputTags
							value={field.value}
							onChange={(newTags) => {
								clearFieldAndRootErrors('tags');
								field.onChange(newTags);
								field.onBlur();
							}}
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

			<h4 className="mt-3">Publicity</h4>
			<Controller
				control={control}
				name="publicity"
				render={({ field }) => (
					<select
						className="form-control"
						value={field.value ? '1' : '0'}
						onFocus={() => setFocusedField('publicity')}
						onChange={(e) => {
							clearFieldAndRootErrors('publicity');
							field.onChange(e.target.value === '1');
						}}
						onBlur={(e) => {
							field.onBlur();
							setFocusedField(null);
						}}
					>
						<option value="1">Public</option>
						<option value="0">Private</option>
					</select>
				)}
			/>
			{publicityError && <div className="text-danger">{publicityError}</div>}

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

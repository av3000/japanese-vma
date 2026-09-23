import { useEffect, useId, useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Input, Label, Select, Textarea } from '@/components/shared/FormControls';
import { InputTags } from '@/components/shared/InputTags';
import Spinner from '@/components/shared/Spinner';
import { Stack } from '@/components/shared/layout';
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
	const idPrefix = useId();
	const fieldId = (field: ArticleFormField) => `${idPrefix}-${field}`;

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
		<Stack as="form" gap="md" onSubmit={handleSubmit(onValidSubmit)}>
			<Field>
				<Label htmlFor={fieldId('title_jp')}>Title (JP)</Label>
				<Input
					id={fieldId('title_jp')}
					maxLength={MAX_TITLE_LENGTH}
					isInvalid={Boolean(titleJpError)}
					{...titleJpField}
					onFocus={() => setFocusedField('title_jp')}
					onBlur={(e) => {
						titleJpField.onBlur(e);
						setFocusedField(null);
					}}
					required={requireTitleContent}
				/>
				<FieldMessage tone={titleJpValue.length >= MAX_TITLE_LENGTH ? 'error' : 'hint'} alignEnd>
					{titleJpValue.length}/{MAX_TITLE_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{titleJpError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={fieldId('title_en')}>Title (EN)</Label>
				<Input
					id={fieldId('title_en')}
					maxLength={MAX_TITLE_LENGTH}
					isInvalid={Boolean(titleEnError)}
					{...titleEnField}
					onFocus={() => setFocusedField('title_en')}
					onBlur={(e) => {
						titleEnField.onBlur(e);
						setFocusedField(null);
					}}
					required={requireEnglishTitle}
				/>
				<FieldMessage tone={titleEnValue.length >= MAX_TITLE_LENGTH ? 'error' : 'hint'} alignEnd>
					{titleEnValue.length}/{MAX_TITLE_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{titleEnError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={fieldId('content_jp')}>Content (JP)</Label>
				<Textarea
					id={fieldId('content_jp')}
					noResize
					rows={7}
					maxLength={MAX_CONTENT_LENGTH}
					isInvalid={Boolean(contentJpError)}
					{...contentJpField}
					onFocus={() => setFocusedField('content_jp')}
					onBlur={(e) => {
						contentJpField.onBlur(e);
						setFocusedField(null);
					}}
					required={requireTitleContent}
				/>
				<FieldMessage tone={contentJpValue.length >= MAX_CONTENT_LENGTH ? 'error' : 'hint'} alignEnd>
					{contentJpValue.length}/{MAX_CONTENT_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{contentJpError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={fieldId('content_en')}>Content (EN)</Label>
				<Textarea
					id={fieldId('content_en')}
					noResize
					rows={5}
					maxLength={MAX_CONTENT_LENGTH}
					isInvalid={Boolean(contentEnError)}
					{...contentEnField}
					onFocus={() => setFocusedField('content_en')}
					onBlur={(e) => {
						contentEnField.onBlur(e);
						setFocusedField(null);
					}}
				/>
				<FieldMessage tone={contentEnValue.length >= MAX_CONTENT_LENGTH ? 'error' : 'hint'} alignEnd>
					{contentEnValue.length}/{MAX_CONTENT_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{contentEnError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={fieldId('source_link')}>Source Link</Label>
				<Input
					id={fieldId('source_link')}
					placeholder="https://www3.nhk.or.jp/news/easy/..."
					maxLength={MAX_SOURCE_LINK_LENGTH}
					isInvalid={Boolean(sourceLinkError)}
					{...sourceLinkField}
					onFocus={() => setFocusedField('source_link')}
					onBlur={(e) => {
						sourceLinkField.onBlur(e);
						setFocusedField(null);
					}}
					required={requireSourceLink}
				/>
				<FieldMessage tone={sourceLinkValue.length >= MAX_SOURCE_LINK_LENGTH ? 'error' : 'hint'} alignEnd>
					{sourceLinkValue.length}/{MAX_SOURCE_LINK_LENGTH}
				</FieldMessage>
				<FieldMessage tone="error">{sourceLinkError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={fieldId('tags')}>Tags</Label>
				<div onFocus={() => setFocusedField('tags')} onBlur={() => setFocusedField(null)}>
					<Controller
						control={control}
						name="tags"
						render={({ field }) => (
							<InputTags
								id={fieldId('tags')}
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
				<FieldMessage tone="error">{tagsError}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={fieldId('publicity')}>Publicity</Label>
				<Controller
					control={control}
					name="publicity"
					render={({ field }) => (
						<Select
							id={fieldId('publicity')}
							isInvalid={Boolean(publicityError)}
							value={field.value ? '1' : '0'}
							onFocus={() => setFocusedField('publicity')}
							onChange={(e) => {
								clearFieldAndRootErrors('publicity');
								field.onChange(e.target.value === '1');
							}}
							onBlur={() => {
								field.onBlur();
								setFocusedField(null);
							}}
						>
							<option value="1">Public</option>
							<option value="0">Private</option>
						</Select>
					)}
				/>
				<FieldMessage tone="error">{publicityError}</FieldMessage>
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

			<FieldMessage tone="error">{statusMessage}</FieldMessage>
			<FieldMessage tone="error">{generalErrorMessage}</FieldMessage>
		</Stack>
	);
}

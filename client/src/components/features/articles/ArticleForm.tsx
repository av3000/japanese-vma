import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch, type FieldPath } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { ApiError } from '@/api/apiError';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { ChoiceGroup, FormField, Input, Textarea } from '@/components/shared/FormControls';
import { FormCard, FormLayout, FormNote } from '@/components/shared/FormPage';
import { InputTags } from '@/components/shared/InputTags';
import { getFieldErrorMessages } from '@/helpers/formErrors';
import { NO_CHANGES_MESSAGE, useApiErrorInForm } from '@/hooks/useApiErrorInForm';
import { VISIBILITY_OPTIONS } from '@/shared/constants/visibility';
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

type ArticleFormField = FieldPath<ArticleFormValues>;

export type ArticleFormSubmitMeta = { dirtyKeys: Extract<keyof ArticleFormValues, string>[] };

/** Visual order: server errors focus the first of these that failed. */
export const ARTICLE_FORM_FIELDS: readonly ArticleFormField[] = [
	'title_jp',
	'title_en',
	'content_jp',
	'content_en',
	'publicity',
	'tags',
	'source_link',
];

/** Update requests name the tags relation `hashtags`. */
const SERVER_FIELD_MAP: Partial<Record<string, ArticleFormField>> = { hashtags: 'tags' };

interface ArticleFormProps {
	initialValues: ArticleFormValues;
	onSubmit: (values: ArticleFormValues, meta: ArticleFormSubmitMeta) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	/** The last rejected save, from `parseApiError`. */
	apiError?: ApiError | null;
	requireEnglishTitle?: boolean;
	/** Edit forms: an unchanged form says so instead of sending an empty update. */
	requireChanges?: boolean;
	/** Body of the "What happens next" note under the settings. */
	note?: ReactNode;
	/** The Cancel control: a link on a page, a close button in a modal. */
	cancel: ReactNode;
	/** One column at every width, for the edit modal. */
	stacked?: boolean;
}

const countOf = (value: string, max: number) => `${value.length} / ${max}`;

export function ArticleForm({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	apiError,
	requireEnglishTitle = false,
	requireChanges = false,
	note,
	cancel,
	stacked = false,
}: ArticleFormProps) {
	const schema = useMemo(() => buildArticleFormSchema({ requireEnglishTitle }), [requireEnglishTitle]);
	const [showNoChanges, setShowNoChanges] = useState(false);

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		formState: { errors, dirtyFields, isDirty },
	} = useForm<ArticleFormValues>({
		defaultValues: initialValues,
		// Errors appear once a field is left, or on submit, and then follow every keystroke.
		mode: 'onTouched',
		resolver: zodResolver(schema),
	});

	const [titleJp, titleEn, contentJp, contentEn, sourceLink] = useWatch({
		control,
		name: ['title_jp', 'title_en', 'content_jp', 'content_en', 'source_link'],
	});

	useEffect(() => {
		reset(initialValues);
	}, [initialValues, reset]);

	useEffect(() => {
		if (isDirty) setShowNoChanges(false);
	}, [isDirty]);

	const alertMessage = useApiErrorInForm(apiError, setError, ARTICLE_FORM_FIELDS, SERVER_FIELD_MAP);

	const onValidSubmit = (values: ArticleFormValues) => {
		if (requireChanges && !isDirty) {
			setShowNoChanges(true);
			return;
		}

		setShowNoChanges(false);
		onSubmit(values, { dirtyKeys: Object.keys(dirtyFields) as ArticleFormSubmitMeta['dirtyKeys'] });
	};

	const alert = showNoChanges ? (
		<Alert tone="info">{NO_CHANGES_MESSAGE}</Alert>
	) : alertMessage ? (
		<Alert tone="danger">{alertMessage}</Alert>
	) : null;

	return (
		<form onSubmit={handleSubmit(onValidSubmit)} noValidate>
			<FormLayout
				stacked={stacked}
				alert={alert}
				aside={
					<>
						<FormCard title="Settings">
							<Controller
								control={control}
								name="publicity"
								render={({ field }) => (
									<ChoiceGroup
										legend="Visibility"
										name={field.name}
										options={VISIBILITY_OPTIONS}
										value={field.value ? 'public' : 'private'}
										onChange={(next) => field.onChange(next === 'public')}
										onBlur={field.onBlur}
										inputRef={field.ref}
										error={getFieldErrorMessages(errors.publicity)}
									/>
								)}
							/>

							<FormField
								label="Tags"
								hint={`Up to ${MAX_TAG_QUANTITY} tags, ${MAX_TAG_LENGTH} characters each. Press Enter, comma or space to add one.`}
								error={getFieldErrorMessages(errors.tags)}
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

							<FormField
								label="Source link"
								hint="Where the text comes from, for example an NHK News Web Easy article."
								counter={countOf(sourceLink ?? '', MAX_SOURCE_LINK_LENGTH)}
								error={getFieldErrorMessages(errors.source_link)}
							>
								{(fieldControl) => (
									<Input
										type="url"
										inputMode="url"
										placeholder="https://www3.nhk.or.jp/news/easy/…"
										maxLength={MAX_SOURCE_LINK_LENGTH}
										required
										{...fieldControl}
										{...register('source_link')}
									/>
								)}
							</FormField>
						</FormCard>
						{note ? <FormNote title="What happens next">{note}</FormNote> : null}
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
						label="Japanese title"
						counter={countOf(titleJp ?? '', MAX_TITLE_LENGTH)}
						error={getFieldErrorMessages(errors.title_jp)}
					>
						{(fieldControl) => (
							<Input
								lang="ja"
								maxLength={MAX_TITLE_LENGTH}
								required
								{...fieldControl}
								{...register('title_jp')}
							/>
						)}
					</FormField>

					<FormField
						label={requireEnglishTitle ? 'English title' : 'English title (optional)'}
						counter={countOf(titleEn ?? '', MAX_TITLE_LENGTH)}
						error={getFieldErrorMessages(errors.title_en)}
					>
						{(fieldControl) => (
							<Input
								maxLength={MAX_TITLE_LENGTH}
								required={requireEnglishTitle}
								{...fieldControl}
								{...register('title_en')}
							/>
						)}
					</FormField>

					<FormField
						label="Japanese text"
						hint="The original text. No markup needed."
						counter={countOf(contentJp ?? '', MAX_CONTENT_LENGTH)}
						error={getFieldErrorMessages(errors.content_jp)}
					>
						{(fieldControl) => (
							<Textarea
								lang="ja"
								rows={10}
								maxLength={MAX_CONTENT_LENGTH}
								required
								{...fieldControl}
								{...register('content_jp')}
							/>
						)}
					</FormField>

					<FormField
						label="English translation (optional)"
						hint="For learners who want to check their understanding."
						counter={countOf(contentEn ?? '', MAX_CONTENT_LENGTH)}
						error={getFieldErrorMessages(errors.content_en)}
					>
						{(fieldControl) => (
							<Textarea
								rows={6}
								maxLength={MAX_CONTENT_LENGTH}
								{...fieldControl}
								{...register('content_en')}
							/>
						)}
					</FormField>
				</FormCard>
			</FormLayout>
		</form>
	);
}

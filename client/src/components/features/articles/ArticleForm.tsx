import React, { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { InputTags } from '@/components/shared/InputTags';

export type ArticleFormFieldKey =
	| 'title_jp'
	| 'title_en'
	| 'content_jp'
	| 'content_en'
	| 'source_link'
	| 'publicity'
	| 'tags'
	| 'hashtags';

export type ArticleFormFieldErrors = Partial<Record<ArticleFormFieldKey, string[]>>;

type ClientValidationErrors = Partial<Record<ArticleFormFieldKey, string>>;

export interface ArticleFormValues {
	title_jp: string;
	title_en: string;
	content_jp: string;
	content_en: string;
	source_link: string;
	publicity: boolean;
	tags: string[];
}

interface ArticleFormProps {
	initialValues: ArticleFormValues;
	onSubmit: (values: ArticleFormValues) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	fieldErrors?: ArticleFormFieldErrors | null;
	statusMessage?: string | null;
	onClearError?: (field: ArticleFormFieldKey) => void;
	requireTitleContent?: boolean;
	requireSourceLink?: boolean;
	requireEnglishTitle?: boolean;
	disableSubmitWhenUnchanged?: boolean;
}

const normalizeText = (value: string) => value.trim();

const normalizeTags = (tags: string[]) => tags.map((t) => t.trim());

const tagsEqual = (a: string[], b: string[]) => a.length === b.length && a.every((t, idx) => t === b[idx]);

const hasDistinctTagsCaseInsensitive = (tags: string[]) => {
	const seen = new Set<string>();
	for (const tag of tags) {
		const normalized = tag.toLowerCase();
		if (seen.has(normalized)) return false;
		seen.add(normalized);
	}
	return true;
};

const isValidUrl = (value: string) => {
	try {
		const url = new URL(value);
		return url.protocol === 'http:' || url.protocol === 'https:';
	} catch {
		return false;
	}
};

const validateArticleForm = (values: ArticleFormValues, requireEnglishTitle: boolean): ClientValidationErrors => {
	const errors: ClientValidationErrors = {};

	const titleJp = normalizeText(values.title_jp);
	const titleEn = normalizeText(values.title_en);
	const contentJp = normalizeText(values.content_jp);
	const contentEn = normalizeText(values.content_en);
	const sourceLink = normalizeText(values.source_link);
	const tags = normalizeTags(values.tags);

	if (titleJp.length === 0) errors.title_jp = 'Japanese title is required.';
	else if (titleJp.length < 2) errors.title_jp = 'Japanese title must be at least 2 characters.';
	else if (titleJp.length > 255) errors.title_jp = 'Japanese title must be at most 255 characters.';

	if (requireEnglishTitle) {
		if (titleEn.length === 0) errors.title_en = 'English title is required.';
		else if (titleEn.length < 2) errors.title_en = 'English title must be at least 2 characters.';
		else if (titleEn.length > 255) errors.title_en = 'English title must be at most 255 characters.';
	} else if (titleEn.length > 0) {
		if (titleEn.length < 2) errors.title_en = 'English title must be at least 2 characters.';
		else if (titleEn.length > 255) errors.title_en = 'English title must be at most 255 characters.';
	}

	if (contentJp.length === 0) errors.content_jp = 'Japanese content is required.';
	else if (contentJp.length < 10) errors.content_jp = 'Japanese content must be at least 10 characters.';
	else if (contentJp.length > 2000) errors.content_jp = 'Japanese content must be at most 2000 characters.';

	if (contentEn.length > 0) {
		if (contentEn.length < 10) errors.content_en = 'English content must be at least 10 characters.';
		else if (contentEn.length > 2000) errors.content_en = 'English content must be at most 2000 characters.';
	}

	if (sourceLink.length === 0) errors.source_link = 'Source link is required.';
	else if (sourceLink.length > 500) errors.source_link = 'Source link must be at most 500 characters.';
	else if (!isValidUrl(sourceLink)) errors.source_link = 'Source link must be a valid http(s) URL.';

	if (tags.length > 10) errors.tags = 'Maximum 10 tags allowed.';
	else if (tags.some((tag) => tag.length === 0)) errors.tags = 'Tags cannot be empty.';
	else if (tags.some((tag) => tag.length > 50)) errors.tags = 'Each tag must not exceed 50 characters.';
	else if (!hasDistinctTagsCaseInsensitive(tags)) errors.tags = 'Duplicate tags are not allowed.';

	return errors;
};

const getFormHasChanged = (values: ArticleFormValues, initialValues: ArticleFormValues) => {
	return (
		normalizeText(values.title_jp) !== normalizeText(initialValues.title_jp) ||
		normalizeText(values.title_en) !== normalizeText(initialValues.title_en) ||
		normalizeText(values.content_jp) !== normalizeText(initialValues.content_jp) ||
		normalizeText(values.content_en) !== normalizeText(initialValues.content_en) ||
		normalizeText(values.source_link) !== normalizeText(initialValues.source_link) ||
		values.publicity !== initialValues.publicity ||
		!tagsEqual(normalizeTags(values.tags), normalizeTags(initialValues.tags))
	);
};

export function ArticleForm({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	fieldErrors,
	statusMessage,
	onClearError,
	requireTitleContent = false,
	requireSourceLink = false,
	requireEnglishTitle = false,
	disableSubmitWhenUnchanged = false,
}: ArticleFormProps) {
	const [form, setForm] = useState<ArticleFormValues>(initialValues);
	const [touched, setTouched] = useState<Partial<Record<ArticleFormFieldKey, boolean>>>({});

	useEffect(() => {
		setForm(initialValues);
		setTouched({});
	}, [initialValues]);

	const formHasChanged = useMemo(() => {
		return getFormHasChanged(form, initialValues);
	}, [form, initialValues]);

	const clientValidationErrors = useMemo(() => {
		return validateArticleForm(form, requireEnglishTitle);
	}, [form, requireEnglishTitle]);

	const isFormValid = useMemo(() => {
		return Object.keys(clientValidationErrors).length === 0;
	}, [clientValidationErrors]);

	const setValue = (key: keyof ArticleFormValues, value: string | string[] | boolean) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const clearError = (field: ArticleFormFieldKey) => {
		onClearError?.(field);
	};

	const markTouched = (field: ArticleFormFieldKey) => {
		setTouched((prev) => (prev[field] ? prev : { ...prev, [field]: true }));
	};

	const onFormSubmit = (e: React.FormEvent) => {
		e.preventDefault();
		console.log('articleForm form', form);
		onSubmit(form);
	};

	const tagError = fieldErrors?.tags?.[0] ?? fieldErrors?.hashtags?.[0];
	const clientTagError = clientValidationErrors.tags;

	const titleJpError = fieldErrors?.title_jp?.[0] ?? (touched.title_jp ? clientValidationErrors.title_jp : undefined);
	const titleEnError = fieldErrors?.title_en?.[0] ?? (touched.title_en ? clientValidationErrors.title_en : undefined);
	const contentJpError =
		fieldErrors?.content_jp?.[0] ?? (touched.content_jp ? clientValidationErrors.content_jp : undefined);
	const contentEnError =
		fieldErrors?.content_en?.[0] ?? (touched.content_en ? clientValidationErrors.content_en : undefined);
	const sourceLinkError =
		fieldErrors?.source_link?.[0] ?? (touched.source_link ? clientValidationErrors.source_link : undefined);
	const tagsError = tagError ?? (touched.tags ? clientTagError : undefined);

	return (
		<form onSubmit={onFormSubmit} className="col-12">
			<h4>Title (JP)</h4>
			<input
				className="form-control"
				value={form.title_jp}
				onChange={(e) => setValue('title_jp', e.target.value)}
				onBlur={() => {
					markTouched('title_jp');
					clearError('title_jp');
				}}
				required={requireTitleContent}
			/>
			{titleJpError && <div className="text-danger">{titleJpError}</div>}

			<h4 className="mt-3">Title (EN)</h4>
			<input
				className="form-control"
				value={form.title_en}
				onChange={(e) => setValue('title_en', e.target.value)}
				onBlur={() => {
					markTouched('title_en');
					clearError('title_en');
				}}
				required={requireEnglishTitle}
			/>
			{titleEnError && <div className="text-danger">{titleEnError}</div>}

			<h4 className="mt-3">Content (JP)</h4>
			<textarea
				className="form-control resize-none"
				rows={7}
				value={form.content_jp}
				onChange={(e) => setValue('content_jp', e.target.value)}
				onBlur={() => {
					markTouched('content_jp');
					clearError('content_jp');
				}}
				required={requireTitleContent}
			/>
			{contentJpError && <div className="text-danger">{contentJpError}</div>}

			<h4 className="mt-3">Content (EN)</h4>
			<textarea
				className="form-control resize-none"
				rows={5}
				value={form.content_en}
				onChange={(e) => setValue('content_en', e.target.value)}
				onBlur={() => {
					markTouched('content_en');
					clearError('content_en');
				}}
			/>
			{contentEnError && <div className="text-danger">{contentEnError}</div>}

			<h4 className="mt-3">Source Link</h4>
			<input
				className="form-control"
				placeholder="https://www3.nhk.or.jp/news/easy/..."
				value={form.source_link}
				onChange={(e) => setValue('source_link', e.target.value)}
				onBlur={() => {
					markTouched('source_link');
					clearError('source_link');
				}}
				required={requireSourceLink}
			/>
			{sourceLinkError && <div className="text-danger">{sourceLinkError}</div>}

			<h4 className="mt-3">Tags</h4>
			<InputTags
				onChange={(newTags) => {
					markTouched('tags');
					setValue('tags', newTags);
				}}
				hideLabel
				label="Tags"
				defaultTags={form.tags}
				maxTags={10}
			/>
			{tagsError && <div className="text-danger">{tagsError}</div>}

			<h4 className="mt-3">Publicity</h4>
			<select
				className="form-control"
				value={form.publicity ? '1' : '0'}
				onChange={(e) => setValue('publicity', e.target.value === '1')}
				onBlur={() => {
					markTouched('publicity');
					clearError('publicity');
				}}
			>
				<option value="1">Public</option>
				<option value="0">Private</option>
			</select>
			{fieldErrors?.publicity?.[0] && <div className="text-danger">{fieldErrors.publicity[0]}</div>}

			<div className="mt-4">
				<Button
					type="submit"
					variant="outline"
					disabled={isSubmitting || (disableSubmitWhenUnchanged && !formHasChanged) || !isFormValid}
				>
					{isSubmitting ? (
						<span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
					) : (
						submitLabel
					)}
				</Button>
			</div>

			{statusMessage && <div className="text-danger mt-3">{statusMessage}</div>}
		</form>
	);
}

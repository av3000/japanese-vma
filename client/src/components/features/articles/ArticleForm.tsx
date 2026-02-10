import React, { useMemo, useEffect, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { parseTags } from './articleFormUtils';

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

export interface ArticleFormValues {
	title_jp: string;
	title_en: string;
	content_jp: string;
	content_en: string;
	source_link: string;
	publicity: boolean;
	tagsText: string;
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
}

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
}: ArticleFormProps) {
	const [form, setForm] = useState<ArticleFormValues>(initialValues);

	useEffect(() => {
		setForm(initialValues);
	}, [initialValues]);

	const tags = useMemo(() => parseTags(form.tagsText), [form.tagsText]);

	const setValue = (key: keyof ArticleFormValues, value: string | boolean) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const clearError = (field: ArticleFormFieldKey) => {
		onClearError?.(field);
	};

	const onFormSubmit = (e: React.FormEvent) => {
		e.preventDefault();
		onSubmit(form);
	};

	const tagError = fieldErrors?.tags?.[0] ?? fieldErrors?.hashtags?.[0];

	return (
		<form onSubmit={onFormSubmit} className="col-12">
			<h4>Title (JP)</h4>
			<input
				className="form-control"
				value={form.title_jp}
				onChange={(e) => setValue('title_jp', e.target.value)}
				onBlur={() => clearError('title_jp')}
				required={requireTitleContent}
			/>
			{fieldErrors?.title_jp?.[0] && <div className="text-danger">{fieldErrors.title_jp[0]}</div>}

			<h4 className="mt-3">Title (EN)</h4>
			<input
				className="form-control"
				value={form.title_en}
				onChange={(e) => setValue('title_en', e.target.value)}
				onBlur={() => clearError('title_en')}
			/>
			{fieldErrors?.title_en?.[0] && <div className="text-danger">{fieldErrors.title_en[0]}</div>}

			<h4 className="mt-3">Content (JP)</h4>
			<textarea
				className="form-control resize-none"
				rows={7}
				value={form.content_jp}
				onChange={(e) => setValue('content_jp', e.target.value)}
				onBlur={() => clearError('content_jp')}
				required={requireTitleContent}
			/>
			{fieldErrors?.content_jp?.[0] && <div className="text-danger">{fieldErrors.content_jp[0]}</div>}

			<h4 className="mt-3">Content (EN)</h4>
			<textarea
				className="form-control resize-none"
				rows={5}
				value={form.content_en}
				onChange={(e) => setValue('content_en', e.target.value)}
				onBlur={() => clearError('content_en')}
			/>
			{fieldErrors?.content_en?.[0] && <div className="text-danger">{fieldErrors.content_en[0]}</div>}

			<h4 className="mt-3">Source Link</h4>
			<input
				className="form-control"
				placeholder="https://www3.nhk.or.jp/news/easy/..."
				value={form.source_link}
				onChange={(e) => setValue('source_link', e.target.value)}
				onBlur={() => clearError('source_link')}
				required={requireSourceLink}
			/>
			{fieldErrors?.source_link?.[0] && <div className="text-danger">{fieldErrors.source_link[0]}</div>}

			<h4 className="mt-3">Tags</h4>
			<input
				className="form-control"
				placeholder="#movie #booktitle or movie,booktitle"
				value={form.tagsText}
				onChange={(e) => setValue('tagsText', e.target.value)}
			/>
			<small className="text-muted">
				Parsed: {tags.length ? tags.map((t) => `#${t}`).join(' ') : '—'}
			</small>
			{tagError && <div className="text-danger">{tagError}</div>}

			<h4 className="mt-3">Publicity</h4>
			<select
				className="form-control"
				value={form.publicity ? '1' : '0'}
				onChange={(e) => setValue('publicity', e.target.value === '1')}
				onBlur={() => clearError('publicity')}
			>
				<option value="1">Public</option>
				<option value="0">Private</option>
			</select>
			{fieldErrors?.publicity?.[0] && <div className="text-danger">{fieldErrors.publicity[0]}</div>}

			<div className="mt-4">
				<Button type="submit" variant="outline" disabled={isSubmitting}>
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

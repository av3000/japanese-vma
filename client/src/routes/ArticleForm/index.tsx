import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CreateArticlePayload, CreateArticleResponse } from '@/api/articles/articles';
import { Button } from '@/components/shared/Button';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';
import axios from '@/services/axios';

async function createArticle(payload: CreateArticlePayload): Promise<CreateArticleResponse> {
	const res = await axios.post('/v1/articles', payload);
	const data = res.data?.data ?? res.data;
	return { uuid: data.uuid };
}

function parseTags(input: string): string[] {
	if (!input.trim()) return [];
	return input
		.split(/[\s,]+/)
		.map((t) => t.trim())
		.filter(Boolean)
		.map((t) => (t.startsWith('#') ? t.slice(1) : t))
		.slice(0, 10);
}

type FieldErrors = Partial<Record<keyof CreateArticlePayload, string[]>>;

export default function ArticleCreatePage() {
	const qc = useQueryClient();
	const navigate = useNavigate();

	// TODO: use some form type validations package. (Zod or something?)
	const [form, setForm] = useState({
		title_jp: '',
		title_en: '',
		content_jp: '',
		content_en: '',
		source_link: '',
		publicity: true,
		tagsText: '',
	});

	const tags = useMemo(() => parseTags(form.tagsText), [form.tagsText]);

	const [status, setStatus] = useState<string | null>(null);
	const [fieldErrors, setFieldErrors] = useState<FieldErrors | null>(null);

	// TODO: add upload image feature
	const mutation = useMutation<CreateArticleResponse, unknown, CreateArticlePayload>({
		mutationFn: createArticle,
		onSuccess: ({ uuid }) => {
			setStatus(null);
			setFieldErrors(null);

			console.log('got back in form uuid of it!', uuid);
			// make lists refetch so the new article appears
			qc.invalidateQueries({ queryKey: ['articles'] });

			navigate(`/articles/${uuid}`);
		},
		onError: (err: any) => {
			const data = err?.response?.data;

			console.log('errorData', data.errors);
			if (isHttpValidationProblemDetails(data)) {
				console.log('isHttpValidationProblem');
				setFieldErrors(data.errors as FieldErrors);
				setStatus(data.title ?? 'Validation failed');
				return;
			}

			console.log('uncaught errror, returns generic error message');

			setFieldErrors(null);
			setStatus('Something went wrong. Please try again.');
			console.error(err);
		},
	});

	const setValue = (key: keyof typeof form, value: string | boolean) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const clearError = (field: keyof CreateArticlePayload) => {
		if (!fieldErrors?.[field]) return;
		const { [field]: _, ...rest } = fieldErrors;
		setFieldErrors(Object.keys(rest).length ? (rest as FieldErrors) : null);
	};

	const onSubmit = (e: React.FormEvent) => {
		e.preventDefault();
		setStatus(null);
		setFieldErrors(null);

		const payload: CreateArticlePayload = {
			title_jp: form.title_jp.trim(),
			title_en: form.title_en.trim() ? form.title_en.trim() : null,
			content_jp: form.content_jp.trim(),
			content_en: form.content_en.trim() ? form.content_en.trim() : null,
			source_link: form.source_link.trim(),
			publicity: form.publicity,
			tags,
		};

		mutation.mutate(payload);
	};

	return (
		<div className="container">
			<div className="row justify-content-lg-center text-center">
				<form onSubmit={onSubmit} className="col-12">
					<h4>Title (JP)</h4>
					<input
						className="form-control"
						value={form.title_jp}
						onChange={(e) => setValue('title_jp', e.target.value)}
						onBlur={() => clearError('title_jp')}
						required
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
						required
					/>
					{fieldErrors?.content_jp?.[0] && <div className="text-danger">{fieldErrors.content_jp[0]}</div>}

					{/* TODO: should show current character count*/}
					{/* TODO: consider text length increasing over 2000, not hard to hit it */}
					{/* TODO: Text should allow to be saved formatted */}
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
						required
					/>
					{fieldErrors?.source_link?.[0] && <div className="text-danger">{fieldErrors.source_link[0]}</div>}

					{/* TODO: Make Chip inputs */}
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
					{fieldErrors?.tags?.[0] && <div className="text-danger">{fieldErrors.tags[0]}</div>}

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
						<Button type="submit" variant="outline" disabled={mutation.isPending}>
							{mutation.isPending ? (
								<span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
							) : (
								'Create'
							)}
						</Button>
					</div>

					{status && <div className="text-danger mt-3">{status}</div>}
				</form>
			</div>
		</div>
	);
}

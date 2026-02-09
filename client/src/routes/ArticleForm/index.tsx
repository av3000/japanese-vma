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

// // @ts-nocheck
// /* eslint-disable */
// import React, { Component } from 'react';
// import { Button } from '@/components/shared/Button';
// import { apiCall } from '../../services/api';
// import { hideLoader, showLoader } from '../../store/actions/application';
// import './ArticleForm.css';

// class ArticleForm extends Component {
// 	constructor(props) {
// 		super(props);
// 		this.state = {
// 			title_jp: '',
// 			title_en: '',
// 			content_en: '',
// 			content_jp: '',
// 			source_link: '',
// 			tags: '',
// 			publicity: true,
// 			isLoading: false,
// 		};

// 		this.handleChange = this.handleChange.bind(this);
// 	}

// 	handleNewArticle = (e) => {
// 		e.preventDefault();

// 		const body = this.state.content_jp + this.state.title_jp;
// 		if (body.length < 4) {
// 			this.props.dispatch(showLoader('Fields are not filled properly!'));
// 			setTimeout(() => {
// 				this.props.dispatch(hideLoader());
// 			}, 3000);

// 			return;
// 		}

// 		const digit = Math.ceil(body.length / 100); // 100chars = 1min
// 		const approxText = 'It should take up to ' + digit + ' minutes.';
// 		this.props.dispatch(showLoader('Creating Article, please wait.', approxText));

// 		const payload = {
// 			title_jp: this.state.title_jp,
// 			content_jp: this.state.content_jp,
// 			source_link: this.state.source_link,
// 			tags: this.state.tags,
// 			publicity: this.state.publicity,
// 			attach: 1,
// 		};

// 		this.postNewArticle(payload);
// 	};

// 	postNewArticle(payload) {
// 		this.setState({ isLoading: true });

// 		return apiCall('post', `/api/article`, payload)
// 			.then((res) => {
// 				this.props.dispatch(hideLoader());
// 				this.setState({ isLoading: false });
// 				this.props.history.push('/article/' + res.article.id);
// 			})
// 			.catch((err) => {
// 				this.props.dispatch(hideLoader());
// 				this.setState({ isLoading: false });
// 				if (err.title_jp) {
// 					return { success: false, err: err.title_jp[0] };
// 				} else if (err.content_jp) {
// 					return { success: false, err: err.content_jp[0] };
// 				} else if (err.source_link) {
// 					return { success: false, err: err.source_link[0] };
// 				} else {
// 					console.log(err);
// 					return { success: false, err };
// 				}
// 			});
// 	}

// 	handleChange(e) {
// 		this.setState({ [e.target.name]: e.target.value });
// 	}

// 	render() {
// 		return (
// 			<div className="container">
// 				<div className="row justify-content-lg-center text-center">
// 					<form onSubmit={this.handleNewArticle} className="col-12">
// 						<label htmlFor="content_jp" className="mt-3">
// 							{' '}
// 							<h4>Title</h4>{' '}
// 						</label>
// 						<input
// 							placeholder="Article title text"
// 							type="text"
// 							className="form-control"
// 							value={this.state.title_jp}
// 							name="title_jp"
// 							onChange={this.handleChange}
// 						/>
// 						<label htmlFor="content_jp" className="mt-3">
// 							{' '}
// 							<h4>Content</h4>{' '}
// 						</label>
// 						<textarea
// 							placeholder="Article body text"
// 							type="text"
// 							className="form-control resize-none"
// 							value={this.state.content_jp}
// 							name="content_jp"
// 							onChange={this.handleChange}
// 							rows="7"
// 						></textarea>
// 						<label htmlFor="content_jp" className="mt-3">
// 							{' '}
// 							<h4>Source Link</h4>{' '}
// 						</label>
// 						<input
// 							placeholder="https://jplearning.online/article/title..."
// 							type="text"
// 							className="form-control"
// 							value={this.state.source_link}
// 							name="source_link"
// 							onChange={this.handleChange}
// 						/>
// 						<label htmlFor="tags" className="mt-3">
// 							{' '}
// 							<h4>Add Tags</h4>{' '}
// 						</label>
// 						<input
// 							placeholder="#movie #booktitle #office"
// 							type="text"
// 							className="form-control"
// 							value={this.state.tags}
// 							name="tags"
// 							onChange={this.handleChange}
// 						/>
// 						<label htmlFor="publicity" className="mt-3">
// 							Publicity
// 						</label>
// 						<select
// 							name="publicity"
// 							value={this.state.publicity}
// 							className="form-control"
// 							onChange={this.handleChange}
// 						>
// 							<option value="1">Public</option>
// 							<option value="0">Private</option>
// 						</select>
// 						<Button type="submit" variant="outline">
// 							{this.state.isLoading ? (
// 								<span
// 									className="spinner-border spinner-border-sm"
// 									role="status"
// 									aria-hidden="true"
// 								></span>
// 							) : (
// 								<span>Create</span>
// 							)}
// 						</Button>
// 					</form>
// 				</div>
// 			</div>
// 		);
// 	}
// }

// export default ArticleForm;

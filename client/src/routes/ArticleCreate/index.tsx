import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createArticle, CreateArticlePayload, CreateArticleResponse } from '@/api/articles/articles';
import { ArticleForm, ArticleFormFieldErrors, ArticleFormValues } from '@/components/features/articles/ArticleForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

export default function ArticleCreatePage() {
	const qc = useQueryClient();
	const navigate = useNavigate();

	const [formErrors, setFormErrors] = useState<ArticleFormFieldErrors | null>(null);
	const [status, setStatus] = useState<string | null>(null);

	const initialValues = useMemo<ArticleFormValues>(() => {
		return {
			title_jp: '',
			title_en: '',
			content_jp: '',
			content_en: '',
			source_link: '',
			publicity: true,
			tags: [],
		};
	}, []);

	// TODO: add upload image feature
	const mutation = useMutation<CreateArticleResponse, unknown, CreateArticlePayload>({
		mutationFn: createArticle,
		onSuccess: ({ uuid }) => {
			setStatus(null);
			setFormErrors(null);

			console.log('got back in form uuid of it!', uuid);
			// make lists refetch so the new article appears
			// TODO: is there a need to invalidate if we navigate and then fetch articles on navigation?
			qc.invalidateQueries({ queryKey: ['articles'] });

			navigate(`/articles/${uuid}`);
		},
		onError: (err: any) => {
			const data = err?.response?.data;

			console.log('errorData', data.errors);
			if (isHttpValidationProblemDetails(data)) {
				console.log('isHttpValidationProblem');
				setFormErrors(data.errors as ArticleFormFieldErrors);
				setStatus(data.title ?? 'Validation failed');
				return;
			}

			console.log('uncaught errror, returns generic error message');

			setFormErrors(null);
			setStatus('Something went wrong. Please try again.');
			console.error(err);
		},
	});

	const clearError = (field: keyof ArticleFormFieldErrors) => {
		if (!formErrors?.[field]) return;
		const { [field]: _, ...rest } = formErrors;
		setFormErrors(Object.keys(rest).length ? (rest as ArticleFormFieldErrors) : null);
	};

	const onSubmit = (values: ArticleFormValues) => {
		setStatus(null);
		setFormErrors(null);

		const payload: CreateArticlePayload = {
			title_jp: values.title_jp.trim(),
			title_en: values.title_en.trim(),
			content_jp: values.content_jp.trim(),
			content_en: values.content_en.trim() ? values.content_en.trim() : null,
			source_link: values.source_link.trim(),
			publicity: values.publicity,
			tags: values.tags,
		};

		console.log('create article payload: ', payload);
		mutation.mutate(payload);
	};

	return (
		<div className="container">
			<div className="row justify-content-lg-center text-center">
				{/* TODO: Step forward would be generic reusable form, accepting fields configs with field types */}
				<ArticleForm
					initialValues={initialValues}
					onSubmit={onSubmit}
					isSubmitting={mutation.isPending}
					submitLabel="Create"
					fieldErrors={formErrors}
					statusMessage={status}
					onClearError={(field) => clearError(field)}
					requireTitleContent
					requireEnglishTitle
					requireSourceLink
				/>
			</div>
		</div>
	);
}

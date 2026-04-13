import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { articleStore } from '@/api/generated/article/article';
import type { StoreArticleRequest } from '@/api/generated/model/storeArticleRequest';
import type { UuidCreatedResponseData } from '@/api/generated/model/uuidCreatedResponseData';
import { ArticleForm, type ArticleFormValues } from '@/components/features/articles/ArticleForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

export default function ArticleCreatePage() {
	const qc = useQueryClient();
	const navigate = useNavigate();

	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
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
	const mutation = useMutation<UuidCreatedResponseData, unknown, StoreArticleRequest>({
		mutationFn: async (payload) => {
			const response = await articleStore(payload);
			return response.data;
		},
		onSuccess: ({ uuid }) => {
			setStatus(null);
			setServerErrors(null);

			// make lists refetch so the new article appears
			// TODO: is there a need to invalidate if we navigate and then fetch articles on navigation?
			qc.invalidateQueries({ queryKey: ['articles'] });

			navigate(`/articles/${uuid}`);
		},
		onError: (err: any) => {
			const data = err?.response?.data;

			if (isHttpValidationProblemDetails(data)) {
				setServerErrors(data.errors);
				setStatus(data.title ?? 'Validation failed');
				return;
			}

			setServerErrors(null);
			setStatus('Something went wrong. Please try again.');
			console.error(err);
		},
	});

	const onSubmit = (values: ArticleFormValues) => {
		setStatus(null);
		setServerErrors(null);

		const payload: StoreArticleRequest = {
			title_jp: values.title_jp.trim(),
			title_en: values.title_en.trim(),
			content_jp: values.content_jp.trim(),
			content_en: values.content_en.trim() ? values.content_en.trim() : null,
			source_link: values.source_link.trim(),
			publicity: values.publicity,
			tags: values.tags,
		};

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
					serverErrors={serverErrors}
					statusMessage={status}
					requireTitleContent
					requireEnglishTitle
					requireSourceLink
				/>
			</div>
		</div>
	);
}

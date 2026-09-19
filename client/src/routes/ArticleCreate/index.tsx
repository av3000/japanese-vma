import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { articleKeys } from '@/api/articles/keys';
import { applyProcessingStatus } from '@/api/articles/processingStatusCache';
import { articleStore } from '@/api/generated/article/article';
import type { ArticleCreatedResource } from '@/api/generated/model/articleCreatedResource';
import type { StoreArticleRequest } from '@/api/generated/model/storeArticleRequest';
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
	const mutation = useMutation<ArticleCreatedResource, unknown, StoreArticleRequest>({
		mutationFn: (payload) => articleStore(payload),
		onSuccess: ({ uuid, processing_status }) => {
			setStatus(null);
			setServerErrors(null);

			// The server opened the `pending` row inside the create transaction (#258), so the
			// first detail fetch already carries it. Write it into whatever cache entries exist
			// through the shared status path; a partial detail object is deliberately NOT seeded,
			// because the detail page renders the full resource and would break on one.
			applyProcessingStatus(qc, uuid, processing_status);

			// Every cached list variant (homepage, dashboard, discovery) must refetch so the new
			// article shows up regardless of which one the user lands on next.
			qc.invalidateQueries({ queryKey: articleKeys.lists() });

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

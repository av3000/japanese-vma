import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { articleKeys } from '@/api/articles/keys';
import { applyProcessingStatus } from '@/api/articles/processingStatusCache';
import { readArticleWriteError, type ArticleWriteFailure } from '@/api/articles/writes';
import { articleStore } from '@/api/generated/article/article';
import type { ArticleCreatedResource } from '@/api/generated/model/articleCreatedResource';
import type { StoreArticleRequest } from '@/api/generated/model/storeArticleRequest';
import { ArticleForm, type ArticleFormValues } from '@/components/features/articles/ArticleForm';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';

const ARTICLES_ROUTE = '/articles';

export default function ArticleCreatePage() {
	const qc = useQueryClient();
	const navigate = useNavigate();

	const [failure, setFailure] = useState<ArticleWriteFailure | null>(null);

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
			setFailure(null);

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
		onError: (error) => setFailure(readArticleWriteError(error)),
	});

	const onSubmit = (values: ArticleFormValues) => {
		setFailure(null);

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
		<FormPage title="New article" backLink={{ to: ARTICLES_ROUTE, label: 'Articles' }}>
			<ArticleForm
				initialValues={initialValues}
				onSubmit={onSubmit}
				isSubmitting={mutation.isPending}
				submitLabel="Create article"
				failure={failure}
				requireEnglishTitle
				note={
					<p>
						We analyse the Japanese text for kanji and words, usually within a minute. You can follow the
						status on the article page and your dashboard. A reviewer may check the article.
					</p>
				}
				cancel={
					<Button variant="ghost" to={ARTICLES_ROUTE}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}

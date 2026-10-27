import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { parseApiError, type ApiError } from '@/api/apiError';
import { getArticleDetailQueryKey, useArticleQuery } from '@/api/articles/details';
import { articleKeys } from '@/api/articles/keys';
import { articleUpdate } from '@/api/generated/article/article';
import type { UpdateArticleRequest } from '@/api/generated/model/updateArticleRequest';
import { isPublic } from '@/api/publicity';
import {
	ArticleForm,
	type ArticleFormSubmitMeta,
	type ArticleFormValues,
} from '@/components/features/articles/ArticleForm';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { PageLoading } from '@/components/shared/PageLoading';
import { useAuth } from '@/hooks/useAuth';

const ARTICLES_LINK = { to: '/articles', label: 'Articles' };

const normalizeOptional = (value: string) => {
	const trimmed = value.trim();
	return trimmed === '' ? null : trimmed;
};

type DirtyKey = ArticleFormSubmitMeta['dirtyKeys'][number];

/** Only the fields the author changed; an emptied English title or text is sent as `null`. */
export const buildArticleUpdatePayload = (values: ArticleFormValues, dirtyKeys: DirtyKey[]): UpdateArticleRequest => {
	return dirtyKeys.reduce<UpdateArticleRequest>((payload, dirtyKey) => {
		if (dirtyKey === 'tags') payload.hashtags = values.tags;
		else if (dirtyKey === 'title_en') payload.title_en = normalizeOptional(values.title_en);
		else if (dirtyKey === 'content_en') payload.content_en = normalizeOptional(values.content_en);
		else payload[dirtyKey] = values[dirtyKey] as any;

		return payload;
	}, {});
};

export default function ArticleEditPage() {
	const { article_id: articleUuid } = useParams<{ article_id: string }>();
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { user } = useAuth();

	// The detail route fills this cache entry, so arriving from the article costs no extra request.
	const { data: article, isLoading, isError } = useArticleQuery(articleUuid);

	const [apiError, setApiError] = useState<ApiError | null>(null);

	const initialValues = useMemo<ArticleFormValues>(
		() => ({
			title_jp: article?.title_jp ?? '',
			title_en: article?.title_en ?? '',
			content_jp: article?.content_jp ?? '',
			content_en: article?.content_en ?? '',
			source_link: article?.source_link ?? '',
			publicity: article ? isPublic(article.publicity) : true,
			tags: article?.hashtags.map((tag) => tag.content) ?? [],
		}),
		[article],
	);

	const updateMutation = useMutation({
		mutationFn: (payload: UpdateArticleRequest) => articleUpdate(article?.uuid ?? '', payload),
		onSuccess: () => {
			if (!article) return;

			queryClient.invalidateQueries({ queryKey: getArticleDetailQueryKey(article.uuid) });
			queryClient.invalidateQueries({ queryKey: articleKeys.lists() });
			navigate(`/articles/${article.uuid}`);
		},
		onError: (error) => setApiError(parseApiError(error)),
	});

	if (isLoading) {
		return <PageLoading family="form" />;
	}

	if (isError || !article) {
		return (
			<FormPage title="Edit article" backLink={ARTICLES_LINK}>
				<Alert tone="danger">Article could not be loaded.</Alert>
			</FormPage>
		);
	}

	const articleRoute = `/articles/${article.uuid}`;

	// Editing is the author's alone, as the Edit action on the article page always was.
	if (user?.id !== article.author.id) {
		return (
			<FormPage title="Edit article" backLink={{ to: articleRoute, label: article.title_jp }}>
				<Alert tone="danger">You do not have permission to edit this article.</Alert>
			</FormPage>
		);
	}

	// The form refuses an unchanged submit itself (`requireChanges`), so dirtyKeys is never empty here.
	const handleSubmit = (values: ArticleFormValues, { dirtyKeys }: ArticleFormSubmitMeta) => {
		setApiError(null);
		updateMutation.mutate(buildArticleUpdatePayload(values, dirtyKeys));
	};

	return (
		<FormPage title="Edit article" backLink={{ to: articleRoute, label: article.title_jp }}>
			<ArticleForm
				initialValues={initialValues}
				onSubmit={handleSubmit}
				isSubmitting={updateMutation.isPending}
				submitLabel="Save changes"
				apiError={apiError}
				requireEnglishTitle
				requireChanges
				note={<p>Changing the Japanese title or text runs the kanji and word analysis again.</p>}
				cancel={
					<Button variant="ghost" to={articleRoute}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}

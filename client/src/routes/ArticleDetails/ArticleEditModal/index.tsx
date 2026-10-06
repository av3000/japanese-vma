import { useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { parseApiError, type ApiError } from '@/api/apiError';
import { getArticleDetailQueryKey, MappedArticle } from '@/api/articles/details';
import { articleKeys } from '@/api/articles/keys';
import { articleUpdate } from '@/api/generated/article/article';
import type { UpdateArticleRequest } from '@/api/generated/model/updateArticleRequest';
import { isPublic } from '@/api/publicity';
import {
	ArticleForm,
	type ArticleFormSubmitMeta,
	type ArticleFormValues,
} from '@/components/features/articles/ArticleForm';
import { Button } from '@/components/shared/Button';
import { DialogModal } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';

interface ArticleEditModalProps {
	article: MappedArticle;
	controller: ModalController;
}

const normalizeOptional = (value: string) => {
	const trimmed = value.trim();
	return trimmed === '' ? null : trimmed;
};

type DirtyKey = ArticleFormSubmitMeta['dirtyKeys'][number];

const buildUpdatePayload = (values: ArticleFormValues, dirtyKeys: DirtyKey[]): UpdateArticleRequest => {
	return dirtyKeys.reduce<UpdateArticleRequest>((payload, dirtyKey) => {
		if (dirtyKey === 'tags') payload.hashtags = values.tags;
		else if (dirtyKey === 'title_en') payload.title_en = normalizeOptional(values.title_en);
		else if (dirtyKey === 'content_en') payload.content_en = normalizeOptional(values.content_en);
		else payload[dirtyKey] = values[dirtyKey] as any;

		return payload;
	}, {});
};

export default function ArticleEditModal({ article, controller }: ArticleEditModalProps) {
	const queryClient = useQueryClient();
	const [apiError, setApiError] = useState<ApiError | null>(null);

	const initialValues: ArticleFormValues = useMemo(
		() => ({
			title_jp: article.title_jp ?? '',
			title_en: article.title_en ?? '',
			content_jp: article.content_jp ?? '',
			content_en: article.content_en ?? '',
			source_link: article.source_link ?? '',
			publicity: isPublic(article.publicity),
			tags: article.hashtags.map((tag) => tag.content),
		}),
		[article],
	);

	const updateMutation = useMutation({
		mutationFn: (payload: UpdateArticleRequest) => articleUpdate(article.uuid, payload),
		onSuccess: () => {
			setApiError(null);
			queryClient.invalidateQueries({ queryKey: getArticleDetailQueryKey(article.uuid) });
			queryClient.invalidateQueries({ queryKey: articleKeys.lists() });
			controller.close();
		},
		onError: (error) => setApiError(parseApiError(error)),
	});

	// The form refuses an unchanged submit itself (`requireChanges`), so dirtyKeys is never empty here.
	const handleSubmit = (values: ArticleFormValues, meta: ArticleFormSubmitMeta) => {
		setApiError(null);

		const payload = buildUpdatePayload(values, meta.dirtyKeys);

		updateMutation.mutate(payload);
	};

	return controller.isRendered ? (
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			size="lg"
			ariaLabel="Edit Article"
		>
			<DialogModal.Header>
				<DialogModal.Title>Edit Article</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>
				<ArticleForm
					initialValues={initialValues}
					onSubmit={handleSubmit}
					isSubmitting={updateMutation.isPending}
					submitLabel="Save changes"
					apiError={apiError}
					requireEnglishTitle
					requireChanges
					stacked
					note={<p>Changing the Japanese title or text runs the kanji and word analysis again.</p>}
					cancel={
						<Button variant="ghost" type="button" onClick={controller.close}>
							Cancel
						</Button>
					}
				/>
			</DialogModal.Body>
		</DialogModal>
	) : null;
}

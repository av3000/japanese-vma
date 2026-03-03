import { useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateArticle, UpdateArticlePayload } from '@/api/articles/articles';
import { MappedArticle } from '@/api/articles/details';
import {
	ArticleForm,
	type ArticleFormSubmitMeta,
	type ArticleFormValues,
} from '@/components/features/articles/ArticleForm';
import { DialogModal } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

interface ArticleEditModalProps {
	article: MappedArticle;
	controller: ModalController;
}

const normalizeOptional = (value: string) => {
	const trimmed = value.trim();
	return trimmed === '' ? null : trimmed;
};

type DirtyKey = ArticleFormSubmitMeta['dirtyKeys'][number];

const buildUpdatePayload = (values: ArticleFormValues, dirtyKeys: DirtyKey[]): UpdateArticlePayload => {
	return dirtyKeys.reduce<UpdateArticlePayload>((payload, dirtyKey) => {
		if (dirtyKey === 'tags') payload.hashtags = values.tags;
		else if (dirtyKey === 'title_en') payload.title_en = normalizeOptional(values.title_en);
		else if (dirtyKey === 'content_en') payload.content_en = normalizeOptional(values.content_en);
		else payload[dirtyKey] = values[dirtyKey] as any;

		return payload;
	}, {});
};

export default function ArticleEditModal({ article, controller }: ArticleEditModalProps) {
	const queryClient = useQueryClient();
	const [status, setStatus] = useState<string | null>(null);
	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);

	const initialValues: ArticleFormValues = useMemo(
		() => ({
			title_jp: article.title_jp ?? '',
			title_en: article.title_en ?? '',
			content_jp: article.content_jp ?? '',
			content_en: article.content_en ?? '',
			source_link: article.source_link ?? '',
			publicity: article.publicity === 1,
			tags: article.hashtags.map((tag) => tag.content),
		}),
		[article],
	);

	const mutation = useMutation({
		mutationFn: (payload: UpdateArticlePayload) => updateArticle(article.uuid, payload),
		onSuccess: () => {
			setStatus(null);
			setServerErrors(null);
			queryClient.invalidateQueries({ queryKey: ['article', article.uuid] });
			queryClient.invalidateQueries({ queryKey: ['articles'] });
			controller.close();
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

	const handleSubmit = (values: ArticleFormValues, meta: ArticleFormSubmitMeta) => {
		setStatus(null);
		setServerErrors(null);

		if (meta.dirtyKeys.length === 0) {
			setStatus('No changes to update.');
			return;
		}

		const payload = buildUpdatePayload(values, meta.dirtyKeys);

		mutation.mutate(payload);
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
				<div className="row justify-content-lg-center text-center">
					<ArticleForm
						initialValues={initialValues}
						onSubmit={handleSubmit}
						isSubmitting={mutation.isPending}
						submitLabel="Update"
						serverErrors={serverErrors}
						statusMessage={status}
						requireEnglishTitle
						disableSubmitWhenUnchanged
					/>
				</div>
			</DialogModal.Body>
		</DialogModal>
	) : null;
}

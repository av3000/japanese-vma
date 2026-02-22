import { useMemo, useState, useEffect } from 'react';
import { Modal } from 'react-bootstrap';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateArticle, UpdateArticlePayload } from '@/api/articles/articles';
import { MappedArticle } from '@/api/articles/details';
import {
	ArticleForm,
	type ArticleFormSubmitMeta,
	type ArticleFormValues,
} from '@/components/features/articles/ArticleForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

interface ArticleEditModalProps {
	article: MappedArticle;
	isOpen: boolean;
	onClose: () => void;
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

export default function ArticleEditModal({ article, isOpen, onClose }: ArticleEditModalProps) {
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

	useEffect(() => {
		if (isOpen) {
			setStatus(null);
			setServerErrors(null);
		}
	}, [isOpen]);

	const mutation = useMutation({
		mutationFn: (payload: UpdateArticlePayload) => updateArticle(article.uuid, payload),
		onSuccess: () => {
			setStatus(null);
			setServerErrors(null);
			queryClient.invalidateQueries({ queryKey: ['article', article.uuid] });
			queryClient.invalidateQueries({ queryKey: ['articles'] });
			onClose();
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

	return (
		<Modal show={isOpen} onHide={onClose} size="lg" centered>
			<Modal.Header closeButton>
				<Modal.Title>Edit Article</Modal.Title>
			</Modal.Header>
			<Modal.Body>
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
			</Modal.Body>
		</Modal>
	);
}

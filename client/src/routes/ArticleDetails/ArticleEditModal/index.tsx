import { useMemo, useState, useEffect } from 'react';
import { Modal } from 'react-bootstrap';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateArticle, UpdateArticlePayload } from '@/api/articles/articles';
import { MappedArticle } from '@/api/articles/details';
import { ArticleForm, ArticleFormFieldErrors, ArticleFormValues } from '@/components/features/articles/ArticleForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

interface ArticleEditModalProps {
	article: MappedArticle;
	isOpen: boolean;
	onClose: () => void;
}

const normalizeText = (value: string) => value.trim();

const normalizeOptional = (value: string) => {
	const trimmed = value.trim();
	return trimmed === '' ? null : trimmed;
};

const buildUpdatePayload = (values: ArticleFormValues, initialValues: ArticleFormValues): UpdateArticlePayload => {
	const payload: UpdateArticlePayload = {};

	const nextTitleJp = normalizeText(values.title_jp);
	const prevTitleJp = normalizeText(initialValues.title_jp);
	if (nextTitleJp !== prevTitleJp) payload.title_jp = nextTitleJp;

	const nextTitleEn = normalizeOptional(values.title_en);
	const prevTitleEn = normalizeOptional(initialValues.title_en);
	if (nextTitleEn !== prevTitleEn) payload.title_en = nextTitleEn;

	const nextContentJp = normalizeText(values.content_jp);
	const prevContentJp = normalizeText(initialValues.content_jp);
	if (nextContentJp !== prevContentJp) payload.content_jp = nextContentJp;

	const nextContentEn = normalizeOptional(values.content_en);
	const prevContentEn = normalizeOptional(initialValues.content_en);
	if (nextContentEn !== prevContentEn) payload.content_en = nextContentEn;

	const nextSourceLink = normalizeText(values.source_link);
	const prevSourceLink = normalizeText(initialValues.source_link);
	if (nextSourceLink !== prevSourceLink) payload.source_link = nextSourceLink;

	if (values.publicity !== initialValues.publicity) payload.publicity = values.publicity;

	const nextTags = [...values.tags];
	const tagsChanged =
		nextTags.length !== initialValues.tags.length || nextTags.some((tag, idx) => tag !== initialValues.tags[idx]);
	if (tagsChanged) payload.hashtags = nextTags;

	return payload;
};

export default function ArticleEditModal({ article, isOpen, onClose }: ArticleEditModalProps) {
	const queryClient = useQueryClient();
	const [status, setStatus] = useState<string | null>(null);
	const [fieldErrors, setFieldErrors] = useState<ArticleFormFieldErrors | null>(null);

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
			setFieldErrors(null);
		}
	}, [isOpen]);

	const mutation = useMutation({
		mutationFn: (payload: UpdateArticlePayload) => updateArticle(article.uuid, payload),
		onSuccess: () => {
			setStatus(null);
			setFieldErrors(null);
			queryClient.invalidateQueries({ queryKey: ['article', article.uuid] });
			queryClient.invalidateQueries({ queryKey: ['articles'] });
			onClose();
		},
		onError: (err: any) => {
			const data = err?.response?.data;
			if (isHttpValidationProblemDetails(data)) {
				setFieldErrors(data.errors as ArticleFormFieldErrors);
				setStatus(data.title ?? 'Validation failed');
				return;
			}

			setFieldErrors(null);
			setStatus('Something went wrong. Please try again.');
			console.error(err);
		},
	});

	const clearError = (field: keyof ArticleFormFieldErrors) => {
		if (!fieldErrors?.[field]) return;
		const { [field]: _, ...rest } = fieldErrors;
		setFieldErrors(Object.keys(rest).length ? (rest as ArticleFormFieldErrors) : null);
	};

	const handleSubmit = (values: ArticleFormValues) => {
		setStatus(null);
		setFieldErrors(null);

		const diffPayload = buildUpdatePayload(values, initialValues);

		if (Object.keys(diffPayload).length === 0) {
			setStatus('No changes to update.');
			return;
		}

		const payload: UpdateArticlePayload = {
			...diffPayload,
			title_en: normalizeText(values.title_en),
		};

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
						fieldErrors={fieldErrors}
						statusMessage={status}
						onClearError={(field) => clearError(field)}
						requireEnglishTitle
						disableSubmitWhenUnchanged
					/>
				</div>
			</Modal.Body>
		</Modal>
	);
}

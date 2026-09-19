import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { MappedArticle, useLikeArticleMutation } from '@/api/articles/details';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { useArticleStatusMutation } from '@/api/articles/moderation';
import { articleDestroy, articleExportKanjisPdf, articleExportWordsPdf } from '@/api/generated/article/article';
import type { ArticleStatus as ArticleStatusValue } from '@/api/generated/model/articleStatus';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import ProcessingStatusAlert from '@/components/features/ProcessingStatusAlert';
import { ArticlePdfModal } from '@/components/features/articles/ArticlePdfModal';
import { ArticleReviewModal } from '@/components/features/articles/ArticleReviewModal';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import ArticleStatus from '@/components/ui/article-status';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { SavedListType } from '@/shared/constants/enums';
import ArticleEditModal from '../ArticleEditModal';
import styles from './ArticleContent.module.css';

interface ArticleContentProps {
	article: MappedArticle;
}

const ArticleContent: React.FC<ArticleContentProps> = ({ article }) => {
	const navigate = useNavigate();
	const [searchParams, setSearchParams] = useSearchParams();
	const { user: currentUser, isAuthenticated } = useAuth();

	const [tempStatus, setTempStatus] = useState<ArticleStatusValue>(article.status as ArticleStatusValue);
	const reviewDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const pdfDialogRef = useRef<HTMLDialogElement | null>(null);
	const editDialogRef = useRef<HTMLDialogElement | null>(null);

	const reviewModal = useModal(reviewDialogRef, { id: 'article-review-modal' });
	const deleteModal = useModal(deleteDialogRef, { id: 'article-delete-modal' });
	const pdfModal = useModal(pdfDialogRef, { id: 'article-pdf-modal' });

	// TODO: this subscription probably should be move up to smart component, but I had issues with conditional renderins and hooks having to be called in the same order???
	useArticleSubscription(article.uuid);

	// TODO: how should this backend call passed onto - directly here or come from parent smart component?
	const likeMutation = useLikeArticleMutation(article.uuid);

	// TODO: Should only call queries propagating up to smart component
	// ex: statusMutation could be called from a dashboard.
	const statusMutation = useArticleStatusMutation(article.uuid, {
		onSuccess: () => reviewModal.close(),
		// Keep the select in step with the status the server still holds.
		onError: () => setTempStatus(article.status as ArticleStatusValue),
	});

	// TODO: Lift this query up and create query function to be reused
	const deleteMutation = useMutation({
		mutationFn: () => articleDestroy(article.uuid),
		onSuccess: () => navigate('/articles'),
	});

	const openEditModal = () => {
		const next = new URLSearchParams(searchParams);
		next.set('edit', '1');
		setSearchParams(next);
	};

	const closeEditModal = () => {
		const next = new URLSearchParams(searchParams);
		next.delete('edit');
		setSearchParams(next);
	};

	const editModal = useModal(editDialogRef, { id: 'article-edit-modal', onClose: closeEditModal });
	const {
		open: openEditDialog,
		close: closeEditDialog,
		isOpen: isEditDialogOpen,
		isRendered: isEditDialogRendered,
	} = editModal;

	// TODO: Lift this query up and create query function to be reused, with pending state to avoid multi calls
	const handleDownloadPdf = async (type: 'kanji' | 'words') => {
		if (!isAuthenticated) return navigate('/login');
		try {
			const res =
				type === 'kanji'
					? await articleExportKanjisPdf(article.uuid, { responseType: 'blob' })
					: await articleExportWordsPdf(article.uuid, { responseType: 'blob' });
			const file = new Blob([res], { type: 'application/pdf' });
			window.open(URL.createObjectURL(file));
		} catch (error) {
			console.error('PDF Download failed', error);
		}
	};

	const isLiked = article.engagement?.is_liked_by_viewer ?? false;
	const isOwner = currentUser?.id === article.author.id;
	const isAdmin = currentUser?.isAdmin;
	const isEditOpen = isOwner && searchParams.get('edit') === '1';

	const handleLikeClick = () => {
		// The endpoint answers an anonymous caller with a 401, so the login redirect happens here
		// rather than as error handling after a pointless request.
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		likeMutation.mutate(article.id);
	};

	useEffect(() => {
		if (isEditOpen) {
			if (!isEditDialogOpen) openEditDialog();
			return;
		}

		if (isEditDialogOpen || isEditDialogRendered) {
			closeEditDialog();
		}
	}, [isEditOpen, isEditDialogOpen, isEditDialogRendered, openEditDialog, closeEditDialog]);

	return (
		<Container size="sm" className={styles.page}>
			<Stack gap="md">
				<div>
					<Link to="/articles" className="tag-link">
						<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to Articles
					</Link>
				</div>

				<ProcessingStatusAlert processing_status={article.processing_status} />

				<h1 className={styles.title} lang="ja">
					{article.title_jp}
				</h1>

				<Cluster justify="between" className={styles.meta}>
					<div>
						<p className={styles.inlineText}>Posted on {article.formattedDate}</p>
						<Cluster gap="xs">
							<span>{article.engagement?.views_count || 0} views | </span>
							{(isOwner || isAdmin) && (
								<Badge variant="secondary">{article.publicity === 1 ? 'Public' : 'Private'}</Badge>
							)}
							{(isOwner || isAdmin) && <ArticleStatus status={article.status} />}
						</Cluster>
					</div>

					<Cluster gap="xs">
						{isAdmin && (
							<Button
								onClick={reviewModal.open}
								variant="ghost"
								size="md"
								aria-controls={reviewModal.id}
								aria-expanded={reviewModal.isOpen}
							>
								Review
							</Button>
						)}
						{isOwner && (
							<>
								<Button
									onClick={deleteModal.open}
									variant="ghost"
									hasOnlyIcon
									aria-label="Delete article"
									aria-controls={deleteModal.id}
									aria-expanded={deleteModal.isOpen}
								>
									<Icon name="trashbinSolid" size="md" />
								</Button>
								<Button onClick={openEditModal} variant="ghost" hasOnlyIcon aria-label="Edit article">
									<Icon name="penSolid" size="md" />
								</Button>
							</>
						)}
					</Cluster>
				</Cluster>

				<img className={styles.cover} src={DefaultArticleImg} alt="Cover" />
				<p className={styles.articleParagraph} lang="ja">
					{article.content_jp}
				</p>

				<Cluster as="section" gap="2xs" aria-label="Tags">
					{article.hashtags?.map((tag) => (
						<Chip readonly key={tag.id} title={tag.content}>
							{tag.content}
						</Chip>
					))}
				</Cluster>

				<hr className={styles.divider} />

				<Cluster justify="between">
					<Cluster gap="md">
						<img src={AvatarImg} alt="user" width="40" className={styles.avatar} />
						<p className={styles.inlineText}>
							Created by <strong>{article.displayName}</strong>
						</p>
					</Cluster>
					<Cluster gap="xs">
						<p className={styles.inlineText}>{article.engagement?.likes_count}</p>
						<Button
							variant="ghost"
							hasOnlyIcon
							aria-label={isLiked ? 'Unlike this article' : 'Like this article'}
							aria-pressed={isLiked}
							disabled={likeMutation.isTogglingInstance(article.id)}
							onClick={handleLikeClick}
						>
							<Icon size="md" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
						</Button>
						{isAuthenticated && (
							<AuthorizedBookmarkWidget
								instanceObjectType={SavedListType.ARTICLES}
								entityId={article.id}
								modalTitle="Choose Articles List to add"
							/>
						)}
						<Button
							variant="ghost"
							hasOnlyIcon
							aria-label="Generate PDF"
							aria-controls={pdfModal.id}
							aria-expanded={pdfModal.isOpen}
							onClick={pdfModal.open}
						>
							<Icon size="md" name="filePdfSolid" />
						</Button>
					</Cluster>
				</Cluster>
			</Stack>

			<div className={styles.comments}>
				<CommentsBlock parent="article" entityId={article.id} entityUuid={article.uuid} />
			</div>

			<ArticleReviewModal
				controller={reviewModal}
				status={tempStatus}
				onStatusChange={setTempStatus}
				onSave={() => statusMutation.mutate(tempStatus)}
				isProcessing={statusMutation.isPending}
			/>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={article.title_jp}
				onDelete={() => deleteMutation.mutate()}
				isProcessing={deleteMutation.isPending}
			/>

			<ArticlePdfModal
				controller={pdfModal}
				onDownload={handleDownloadPdf}
				isDownloadEnabled={article?.processing_status?.status === LastOperationStatus.completed}
			/>

			{editModal.isRendered && <ArticleEditModal article={article} controller={editModal} />}
		</Container>
	);
};
export default ArticleContent;

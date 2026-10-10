import React, { useEffect, useId, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { MappedArticle, useLikeArticleMutation } from '@/api/articles/details';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { useDeleteArticleMutation } from '@/api/articles/hooks/useDeleteArticleMutation';
import { useArticleStatusMutation } from '@/api/articles/moderation';
import { isProcessingRunning, useArticleReadingStats } from '@/api/articles/readingStats';
import { articleExportKanjisPdf, articleExportWordsPdf } from '@/api/generated/article/article';
import type { ArticleStatus as ArticleStatusValue } from '@/api/generated/model/articleStatus';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import ProcessingStatusAlert from '@/components/features/ProcessingStatusAlert';
import { ArticleAttachments } from '@/components/features/articles/ArticleAttachments';
import { ArticleBody, ArticleTitle } from '@/components/features/articles/ArticleBody';
import { ArticlePdfModal } from '@/components/features/articles/ArticlePdfModal';
import { ArticleReviewModal } from '@/components/features/articles/ArticleReviewModal';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { Button } from '@/components/shared/Button';
import { Byline } from '@/components/shared/Byline';
import { Chip } from '@/components/shared/Chip';
import { DetailActionGroup, DetailActions } from '@/components/shared/DetailActions';
import { characterCount, DetailFacts, type DetailFact } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { Icon } from '@/components/shared/Icon';
import { JlptBar } from '@/components/shared/JlptBar';
import { Link } from '@/components/shared/Link';
import { articleStatusPill, StatusPill } from '@/components/shared/StatusPill';
import { VisibilityCue } from '@/components/shared/VisibilityCue';
import { Cluster } from '@/components/shared/layout';
import { downloadFile, toDownloadFileName } from '@/helpers/downloadFile';
import { isValidHttpUrl } from '@/helpers/isValidHttpUrl';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { SavedListType } from '@/shared/constants/enums';
import ArticleEditModal from '../ArticleEditModal';
import styles from './ArticleContent.module.css';

interface ArticleContentProps {
	article: MappedArticle;
}

/**
 * Where the Edit action goes. Edit is still the modal on this page (`?edit=1`); #447 moves it to
 * `/articles/:id/edit`, and then only this line changes.
 */
export const articleEditHref = (articleUuid: string) => `/articles/${articleUuid}?edit=1`;

/** The hostname of a source link, or `null` when it is not a usable http(s) URL. */
const sourceHostOf = (link: string | null | undefined): string | null =>
	link && isValidHttpUrl(link) ? new URL(link).hostname.replace(/^www\./, '') : null;

const ArticleContent: React.FC<ArticleContentProps> = ({ article }) => {
	const navigate = useNavigate();
	const [searchParams, setSearchParams] = useSearchParams();
	const { user: currentUser, isAuthenticated } = useAuth();
	const tagsHeadingId = useId();
	const commentsHeadingId = useId();

	const [tempStatus, setTempStatus] = useState<ArticleStatusValue>(article.status as ArticleStatusValue);
	const [pdfPendingType, setPdfPendingType] = useState<'kanji' | 'words' | null>(null);
	const [pdfErrorMessage, setPdfErrorMessage] = useState<string | null>(null);
	const reviewDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const pdfDialogRef = useRef<HTMLDialogElement | null>(null);
	const editDialogRef = useRef<HTMLDialogElement | null>(null);

	const reviewModal = useModal(reviewDialogRef, { id: 'article-review-modal' });
	const deleteModal = useModal(deleteDialogRef, { id: 'article-delete-modal' });
	const pdfModal = useModal(pdfDialogRef, { id: 'article-pdf-modal' });

	// Live processing updates write into the detail cache this page renders from.
	useArticleSubscription(article.uuid);

	const likeMutation = useLikeArticleMutation(article.uuid);
	const readingStats = useArticleReadingStats(article);

	const statusMutation = useArticleStatusMutation(article.uuid, {
		onSuccess: () => reviewModal.close(),
		// Keep the select in step with the status the server still holds.
		onError: () => setTempStatus(article.status as ArticleStatusValue),
	});

	const deleteMutation = useDeleteArticleMutation();

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

	const handleDownloadPdf = async (type: 'kanji' | 'words') => {
		if (!isAuthenticated) return navigate('/login');

		setPdfPendingType(type);
		setPdfErrorMessage(null);

		try {
			const res =
				type === 'kanji'
					? await articleExportKanjisPdf(article.uuid, { responseType: 'blob' })
					: await articleExportWordsPdf(article.uuid, { responseType: 'blob' });
			const file = new Blob([res], { type: 'application/pdf' });

			downloadFile(toDownloadFileName(article.title_jp, `article-${type}`, 'pdf'), file);
		} catch (error) {
			console.error('PDF Download failed', error);
			setPdfErrorMessage('The PDF could not be generated. Please try again.');
		} finally {
			setPdfPendingType(null);
		}
	};

	const isLiked = article.engagement?.is_liked_by_viewer ?? false;
	const likesCount = Number(article.engagement?.likes_count ?? 0);
	const isOwner = currentUser?.id === article.author.id;
	const isAdmin = Boolean(currentUser?.isAdmin);
	const isEditOpen = isOwner && searchParams.get('edit') === '1';
	const isProcessing = isProcessingRunning(article);
	const sourceHost = sourceHostOf(article.source_link);

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

	// "Words" is left out, not shown as a guess, when its list could not be read.
	const facts: DetailFact[] = [
		{ term: 'Kanji', value: readingStats.kanji },
		...(readingStats.words === undefined ? [] : [{ term: 'Words', value: readingStats.words }]),
		{ term: 'Characters', value: characterCount(article.content_jp) },
	];

	return (
		<>
			<DetailLayout
				railLabel="About this article"
				header={
					<div className={styles.header}>
						<Link to="/articles" className={styles.back}>
							<Icon name="arrowDownSolid" rotate="90" size="sm" /> Articles
						</Link>
						<ArticleTitle titleJp={article.title_jp} titleEn={article.title_en} />
						<Byline
							name={article.author?.name}
							date={article.created_at}
							views={Number(article.engagement?.views_count ?? 0)}
						/>
						{isOwner || isAdmin ? (
							<Cluster gap="xs" className={styles.ownerCues}>
								<VisibilityCue publicity={article.publicity} />
								<StatusPill {...articleStatusPill(article.status)} />
							</Cluster>
						) : null}
					</div>
				}
				main={
					<div className={styles.main}>
						<ProcessingStatusAlert processing_status={article.processing_status} isOwner={isOwner} />
						<ArticleBody contentJp={article.content_jp} contentEn={article.content_en} />
					</div>
				}
				facts={
					<DetailFacts title="In this reading" facts={facts}>
						{readingStats.kanji ? <JlptBar levels={article.jlpt_levels} /> : null}
					</DetailFacts>
				}
				actions={
					<DetailActions>
						<Button
							variant="outline"
							isFullWidth
							aria-pressed={isLiked}
							disabled={likeMutation.isTogglingInstance(article.id)}
							onClick={handleLikeClick}
						>
							<Icon size="sm" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
							{`Like · ${likesCount}`}
						</Button>
						{isAuthenticated && (
							<AuthorizedBookmarkWidget
								instanceObjectType={SavedListType.ARTICLES}
								entityId={article.id}
								modalTitle="Choose Articles List to add"
								label="Save to a catalogue"
								savedLabel="Saved to a catalogue"
							/>
						)}
						<Button
							variant="outline"
							isFullWidth
							aria-controls={pdfModal.id}
							aria-expanded={pdfModal.isOpen}
							onClick={pdfModal.open}
						>
							<Icon size="sm" name="filePdfSolid" />
							Kanji &amp; words PDF
						</Button>
						{isOwner && (
							<DetailActionGroup heading="Your article">
								<Button variant="outline" isFullWidth to={articleEditHref(article.uuid)}>
									<Icon size="sm" name="penSolid" />
									Edit
								</Button>
								<Button
									variant="outline"
									isFullWidth
									aria-controls={deleteModal.id}
									aria-expanded={deleteModal.isOpen}
									onClick={deleteModal.open}
								>
									<Icon size="sm" name="trashbinSolid" />
									Delete
								</Button>
							</DetailActionGroup>
						)}
						{/* Review stays here until moderation moves to the admin panel (#184). */}
						{isAdmin && (
							<DetailActionGroup heading="Moderation">
								<Button
									variant="outline"
									isFullWidth
									aria-controls={reviewModal.id}
									aria-expanded={reviewModal.isOpen}
									onClick={reviewModal.open}
								>
									Review
								</Button>
							</DetailActionGroup>
						)}
					</DetailActions>
				}
				extra={
					article.hashtags.length > 0 || sourceHost ? (
						<div className={styles.extra}>
							{article.hashtags.length > 0 ? (
								<section aria-labelledby={tagsHeadingId}>
									<h2 id={tagsHeadingId} className={styles.railHeading}>
										Tags
									</h2>
									<Cluster as="ul" gap="2xs" className={styles.tags}>
										{article.hashtags.map((tag) => (
											<li key={tag.id}>
												<Chip readonly title={tag.content}>
													{tag.content}
												</Chip>
											</li>
										))}
									</Cluster>
								</section>
							) : null}
							{sourceHost ? (
								<p className={styles.source}>
									Source:{' '}
									<Link linkUrl={article.source_link} rel="noopener noreferrer">
										{sourceHost}
									</Link>
								</p>
							) : null}
						</div>
					) : null
				}
				after={
					<div className={styles.after}>
						<ArticleAttachments
							articleUuid={article.uuid}
							showSave={isAuthenticated}
							isProcessing={isProcessing}
						/>
						<section aria-labelledby={commentsHeadingId} className={styles.comments}>
							<h2 id={commentsHeadingId} className={styles.sectionHeading}>
								Comments
							</h2>
							<CommentsBlock parent="article" entityId={article.id} entityUuid={article.uuid} />
						</section>
					</div>
				}
			/>

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
				onDelete={() => deleteMutation.mutate(article.uuid, { onSuccess: () => navigate('/articles') })}
				isProcessing={deleteMutation.isPending}
			/>

			<ArticlePdfModal
				controller={pdfModal}
				onDownload={handleDownloadPdf}
				pendingType={pdfPendingType}
				errorMessage={pdfErrorMessage}
				isDownloadEnabled={article?.processing_status?.status === ProcessingStatus.completed}
			/>

			{editModal.isRendered && <ArticleEditModal article={article} controller={editModal} />}
		</>
	);
};
export default ArticleContent;

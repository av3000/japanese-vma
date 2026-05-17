import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import classNames from 'classnames';
import { setArticleStatus } from '@/api/articles/articles';
import { MappedArticle, useLikeArticleMutation } from '@/api/articles/details';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import {
	applyCatalogueForItemAction,
	type CatalogueForItem,
	fetchCataloguesForItem,
	type CatalogueForItemAction,
} from '@/api/catalogues/cataloguesForItem';
import { articleDestroy, articleExportKanjisPdf } from '@/api/generated/article/article';
import { catalogueAddItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import ProcessingStatusAlert from '@/components/features/ProcessingStatusAlert';
import { ArticlePdfModal } from '@/components/features/articles/ArticlePdfModal';
import { ArticleReviewModal } from '@/components/features/articles/ArticleReviewModal';
import { CatalogueBookmarkModal } from '@/components/features/catalogues/CatalogueBookmarkModal';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import ArticleStatus from '@/components/ui/article-status';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { apiCall } from '@/services/api';
import { BASE_URL } from '@/shared/constants';
import { ObjectTemplateType } from '@/shared/constants/enums';
import { HttpMethod } from '@/shared/types';
import ArticleEditModal from '../ArticleEditModal';
import styles from './ArticleContent.module.scss';

interface ArticleContentProps {
	article: MappedArticle;
}

const ArticleContent: React.FC<ArticleContentProps> = ({ article }) => {
	const navigate = useNavigate();
	const [searchParams, setSearchParams] = useSearchParams();
	const queryClient = useQueryClient();
	const { user: currentUser, isAuthenticated } = useAuth();

	const [tempStatus, setTempStatus] = useState<number>(article.status);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);
	const bookmarkDialogRef = useRef<HTMLDialogElement | null>(null);
	const reviewDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const pdfDialogRef = useRef<HTMLDialogElement | null>(null);
	const editDialogRef = useRef<HTMLDialogElement | null>(null);

	const bookmarkModal = useModal(bookmarkDialogRef, { id: 'article-bookmark-modal' });
	const reviewModal = useModal(reviewDialogRef, { id: 'article-review-modal' });
	const deleteModal = useModal(deleteDialogRef, { id: 'article-delete-modal' });
	const pdfModal = useModal(pdfDialogRef, { id: 'article-pdf-modal' });

	// TODO: this subscription probably should be move up to smart component, but I had issues with conditional renderins and hooks having to be called in the same order???
	useArticleSubscription(article.uuid);

	const { data: userLists = [] } = useQuery({
		queryKey: ['article-bookmarks', article.id],
		queryFn: () => fetchCataloguesForItem(article.id),
		enabled: isAuthenticated,
	});

	// TODO: how should this backend call passed onto - directly here or come from parent smart component?
	const likeMutation = useLikeArticleMutation(article.uuid);

	// TODO: Should only call queries propagating up to smart component
	// ex: statusMutation could be called from a dashboard.
	const statusMutation = useMutation({
		mutationFn: (status: number) => setArticleStatus(article.id.toString(), status),
		onSuccess: (res) => {
			queryClient.setQueryData(['article', article.uuid], (old: any) => ({
				...old,
				status: res.data.newStatus,
			}));
			reviewModal.close();
		},
	});

	// TODO: move up to queries, and hide API details.
	const deleteMutation = useMutation({
		mutationFn: () => articleDestroy(article.uuid),
		onSuccess: () => navigate('/articles'),
	});

	// TODO: move up to queries and hide API details, only passing in the params.
	const catalogueMembershipMutation = useMutation<
		unknown,
		unknown,
		{
			list: CatalogueForItem;
			action: CatalogueForItemAction;
		}
	>({
		mutationFn: ({ list, action }: { list: CatalogueForItem; action: CatalogueForItemAction }) => {
			if (action === 'add') {
				return catalogueAddItem(list.uuid, { item_id: article.id });
			}

			return catalogueRemoveItem(list.uuid, article.id);
		},
		onSuccess: (_, { list, action }) => {
			queryClient.setQueryData(['article-bookmarks', article.id], (oldLists = userLists) => {
				return applyCatalogueForItemAction(oldLists as CatalogueForItem[], list.id, action);
			});
		},
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

	const handleListAction = async (list: CatalogueForItem, action: CatalogueForItemAction) => {
		setLoadingListIds((prev) => [...prev, list.id]);
		try {
			await catalogueMembershipMutation.mutateAsync({ list, action });
		} catch (error) {
			console.error('List action failed', error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
	};

	// TODO: Refactor to queries when backend is migrated to V1 endpoint for PDF endpoints.
	// TODO: Should only call queries propagating up to smart component
	const handleDownloadPdf = async (type: 'kanji' | 'words') => {
		if (!isAuthenticated) return navigate('/login');
		try {
			const res =
				type === 'kanji'
					? await articleExportKanjisPdf(article.uuid, { responseType: 'blob' })
					: await apiCall({
							method: HttpMethod.GET,
							path: `${BASE_URL}/api/article/${article.id}/words-pdf`,
							config: { responseType: 'blob' },
						});
			const file = new Blob([res], { type: 'application/pdf' });
			window.open(URL.createObjectURL(file));
		} catch (error) {
			console.error('PDF Download failed', error);
		}
	};

	const isBookmarked = userLists.some((list) => list.contains_item);
	const isLiked = article.engagement?.is_liked_by_viewer;
	const isOwner = currentUser?.id === article.author.id;
	const isAdmin = currentUser?.isAdmin;
	const isEditOpen = isOwner && searchParams.get('edit') === '1';

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
		<div className="container pb-5">
			<div className="row justify-content-center">
				<div className="col-lg-8">
					<span className="row mt-4">
						<Link to="/articles" className="tag-link">
							<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to Articles
						</Link>
					</span>

					<ProcessingStatusAlert processing_status={article.processing_status} />

					<h1 className="mt-4">{article.title_jp}</h1>

					<div className="row text-muted w-100 mb-3 justify-content-between align-items-center">
						<div className="col">
							Posted on {article.formattedDate} <br />
							<span>{article.engagement?.views_count || 0} views | </span>
							{(isOwner || isAdmin) && (
								<Badge variant="secondary" className="mr-2">
									{article.publicity === 1 ? 'Public' : 'Private'}
								</Badge>
							)}
							{(isOwner || isAdmin) && <ArticleStatus status={article.status} />}
						</div>

						<div className="d-flex align-items-center">
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
								<div className="d-flex ml-2">
									<Button
										onClick={deleteModal.open}
										variant="ghost"
										hasOnlyIcon
										aria-controls={deleteModal.id}
										aria-expanded={deleteModal.isOpen}
									>
										<Icon name="trashbinSolid" size="md" />
									</Button>
									<Button onClick={openEditModal} variant="ghost" hasOnlyIcon>
										<Icon name="penSolid" size="md" />
									</Button>
								</div>
							)}
						</div>
					</div>

					<img className="img-fluid rounded mb-3 w-100" src={DefaultArticleImg} alt="Cover" />
					<p className={classNames(styles.articleParagraph, 'lead')}>{article.content_jp}</p>

					<section className="mt-2 d-flex align-items-center flex-wrap">
						{article.hashtags?.map((tag) => (
							<Chip className="mr-1 mb-1" readonly key={tag.id} title={tag.content}>
								{tag.content}
							</Chip>
						))}
					</section>

					<hr className="my-4" />

					<div className="d-flex justify-content-between align-items-center">
						<div className="d-flex align-items-center">
							<img src={AvatarImg} alt="user" width="40" className="rounded-circle" />
							<p className="ml-3 mb-0">
								Created by <strong>{article.displayName}</strong>
							</p>
						</div>
						<div className="d-flex align-items-center">
							<p className="mb-0 mr-2">{article.engagement?.likes_count}</p>
							<Button variant="ghost" hasOnlyIcon onClick={() => likeMutation.mutate(article.id)}>
								<Icon size="md" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
							</Button>
							<Button
								variant="ghost"
								hasOnlyIcon
								aria-controls={bookmarkModal.id}
								aria-expanded={bookmarkModal.isOpen}
								onClick={bookmarkModal.open}
							>
								<Icon size="md" name={isBookmarked ? 'bookmarkSolid' : 'bookmarkRegular'} />
							</Button>
							<Button
								variant="ghost"
								hasOnlyIcon
								aria-controls={pdfModal.id}
								aria-expanded={pdfModal.isOpen}
								onClick={pdfModal.open}
							>
								<Icon size="md" name="filePdfSolid" />
							</Button>
						</div>
					</div>
				</div>
			</div>

			<div className="row justify-content-center mt-5">
				<div className="col-lg-8">
					<CommentsBlock
						readObjectType="article"
						readObjectUuid={article.uuid}
						entityId={article.id}
						entityType={ObjectTemplateType.ARTICLE}
						entityUuid={article.uuid}
					/>
				</div>
			</div>

			<CatalogueBookmarkModal
				controller={bookmarkModal}
				lists={userLists}
				loadingListIds={loadingListIds}
				onListAction={handleListAction}
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
				onDelete={() => deleteMutation.mutate()}
				isProcessing={deleteMutation.isPending}
			/>

			<ArticlePdfModal
				controller={pdfModal}
				onDownload={handleDownloadPdf}
				isDownloadEnabled={article?.processing_status?.status === LastOperationStatus.completed}
			/>

			{editModal.isRendered && <ArticleEditModal article={article} controller={editModal} />}
		</div>
	);
};
export default ArticleContent;

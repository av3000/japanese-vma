import React, { useState } from 'react';
import { Modal } from 'react-bootstrap';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import classNames from 'classnames';
import { deleteArticle, fetchArticleSavedLists, setArticleStatus } from '@/api/articles/articles';
import { MappedArticle, useLikeArticleMutation } from '@/api/articles/details';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import ProcessingStatusAlert from '@/components/features/ProcessingStatusAlert';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import ArticleStatus from '@/components/ui/article-status';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { LIST_ACTIONS, BASE_URL } from '@/shared/constants';
import { HttpMethod } from '@/shared/types';
import styles from './ArticleContent.module.scss';

interface ArticleContentProps {
	article: MappedArticle;
}

const ArticleContent: React.FC<ArticleContentProps> = ({ article }) => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { user: currentUser, isAuthenticated } = useAuth();

	const [modals, setModals] = useState({
		showBookmark: false,
		showPdf: false,
		showDelete: false,
		showStatus: false,
	});
	const [tempStatus, setTempStatus] = useState<number>(article.status);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);

	useArticleSubscription(article.uuid);

	const { data: userLists = [] } = useQuery({
		queryKey: ['article-bookmarks', article.id],
		queryFn: () => fetchArticleSavedLists(article.id.toString()),
		enabled: isAuthenticated,
	});

	const likeMutation = useLikeArticleMutation(article.uuid);

	const statusMutation = useMutation({
		mutationFn: (status: number) => setArticleStatus(article.uuid, status),
		onSuccess: (res) => {
			queryClient.setQueryData(['article', article.uuid], (old: any) => ({
				...old,
				status: res.data.newStatus,
			}));
			setModals((p) => ({ ...p, showStatus: false }));
		},
	});

	const deleteMutation = useMutation({
		mutationFn: () => deleteArticle(article.id),
		onSuccess: () => navigate('/articles'),
	});

	const toggleModal = (modalName: keyof typeof modals) => {
		setModals((prev) => ({ ...prev, [modalName]: !prev[modalName] }));
	};

	// TODO: Refactor to queries when backend is migrated to V1 endpoint for saved lists endpoints.
	const handleListAction = async (listId: number, action: string) => {
		setLoadingListIds((prev) => [...prev, listId]);
		try {
			const endpoint = action === LIST_ACTIONS.ADD_ITEM ? 'additemwhileaway' : 'removeitemwhileaway';
			await apiCall({
				method: HttpMethod.POST,
				path: `${BASE_URL}/api/user/list/${endpoint}`,
				data: { listId, elementId: article.id },
			});

			queryClient.setQueryData(['article-bookmarks', article.id], (oldLists: any[]) => {
				return oldLists?.map((list) =>
					list.id === listId ? { ...list, elementBelongsToList: action === LIST_ACTIONS.ADD_ITEM } : list,
				);
			});
		} catch (error) {
			console.error('List action failed', error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== listId));
		}
	};

	// TODO: Refactor to queries when backend is migrated to V1 endpoint for PDF endpoints.
	const handleDownloadPdf = async (type: 'kanji' | 'words') => {
		if (!isAuthenticated) return navigate('/login');
		try {
			const pdfType = type === 'kanji' ? 'kanjis-pdf' : 'words-pdf';
			const url = `${BASE_URL}/api/article/${article.id}/${pdfType}`;
			const res: any = await apiCall({ method: HttpMethod.GET, path: url, config: { responseType: 'blob' } });
			const file = new Blob([res], { type: 'application/pdf' });
			window.open(URL.createObjectURL(file));
		} catch (error) {
			console.error('PDF Download failed', error);
		}
	};

	const isBookmarked = userLists.some((l: any) => l.elementBelongsToList);
	const isLiked = article.engagement?.is_liked_by_viewer;
	const isOwner = currentUser?.id === article.author.id;
	const isAdmin = currentUser?.isAdmin;

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
								<Button onClick={() => toggleModal('showStatus')} variant="ghost" size="md">
									Review
								</Button>
							)}
							{isOwner && (
								<div className="d-flex ml-2">
									<Button onClick={() => toggleModal('showDelete')} variant="ghost" hasOnlyIcon>
										<Icon name="trashbinSolid" size="md" />
									</Button>
									<Button to={`/article/edit/${article.id}`} variant="ghost" hasOnlyIcon>
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
							<Button variant="ghost" hasOnlyIcon onClick={() => toggleModal('showBookmark')}>
								<Icon size="md" name={isBookmarked ? 'bookmarkSolid' : 'bookmarkRegular'} />
							</Button>
							<Button variant="ghost" hasOnlyIcon onClick={() => toggleModal('showPdf')}>
								<Icon size="md" name="filePdfSolid" />
							</Button>
						</div>
					</div>
				</div>
			</div>

			<div className="row justify-content-center mt-5">
				<div className="col-lg-8">
					<CommentsBlock parentObjectId={article.id} parentObjectType="article" objectUuid={article.uuid} />
				</div>
			</div>

			<Modal show={modals.showBookmark} onHide={() => toggleModal('showBookmark')}>
				<Modal.Header closeButton>
					<Modal.Title>Save to List</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					{userLists.length === 0 && <p className="text-muted">You have no lists created.</p>}
					{userLists.map((list: any) => (
						<div key={list.id} className="d-flex justify-content-between align-items-center mb-2">
							<Link to={`/list/${list.id}`}>{list.title}</Link>
							<Button
								variant={list.elementBelongsToList ? 'danger' : 'primary'}
								size="sm"
								onClick={() =>
									handleListAction(
										list.id,
										list.elementBelongsToList ? LIST_ACTIONS.REMOVE_ITEM : LIST_ACTIONS.ADD_ITEM,
									)
								}
								disabled={loadingListIds.includes(list.id)}
							>
								{loadingListIds.includes(list.id) ? (
									<span className="spinner-border spinner-border-sm" />
								) : list.elementBelongsToList ? (
									'Remove'
								) : (
									'Add'
								)}
							</Button>
						</div>
					))}
					<div className="mt-3 text-right">
						<Link to="/newlist" className="small">
							+ Create a new list
						</Link>
					</div>
				</Modal.Body>
			</Modal>

			<Modal show={modals.showStatus} onHide={() => toggleModal('showStatus')}>
				<Modal.Header closeButton>
					<Modal.Title>Review Article</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					<p>Change Visibility/Approval Status</p>
					<select
						className="form-control"
						value={tempStatus}
						onChange={(e) => setTempStatus(Number(e.target.value))}
					>
						<option value={0}>Pending</option>
						<option value={1}>Review</option>
						<option value={2}>Reject</option>
						<option value={3}>Approve</option>
					</select>
				</Modal.Body>
				<Modal.Footer>
					<Button variant="secondary" onClick={() => toggleModal('showStatus')}>
						Cancel
					</Button>
					<Button
						variant="success"
						onClick={() => statusMutation.mutate(tempStatus)}
						disabled={statusMutation.isPending}
					>
						{statusMutation.isPending ? 'Saving...' : 'Save Changes'}
					</Button>
				</Modal.Footer>
			</Modal>

			<Modal show={modals.showDelete} onHide={() => toggleModal('showDelete')}>
				<Modal.Header closeButton>
					<Modal.Title>Are you absolutely sure?</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					This action cannot be undone. This will permanently delete <strong>{article.title_jp}</strong>.
				</Modal.Body>
				<Modal.Footer>
					<Button variant="secondary" onClick={() => toggleModal('showDelete')}>
						Cancel
					</Button>
					<Button
						variant="danger"
						onClick={() => deleteMutation.mutate()}
						disabled={deleteMutation.isPending}
					>
						{deleteMutation.isPending ? 'Deleting...' : 'Yes, Delete Article'}
					</Button>
				</Modal.Footer>
			</Modal>

			<Modal show={modals.showPdf} onHide={() => toggleModal('showPdf')} size="sm" centered>
				<Modal.Body className="text-center p-4">
					<h5 className="mb-4">Generate PDF</h5>
					<Button
						variant="ghost"
						className="w-100 mb-2 border"
						disabled={article?.processing_status?.status !== LastOperationStatus.Completed}
						onClick={() => handleDownloadPdf('kanji')}
					>
						Kanji List <Icon size="sm" name="filePdfSolid" className="ml-2" />
					</Button>
					<Button variant="ghost" className="w-100 border" onClick={() => handleDownloadPdf('words')}>
						Vocabulary List <Icon size="sm" name="filePdfSolid" className="ml-2" />
					</Button>
				</Modal.Body>
			</Modal>
		</div>
	);
};
export default ArticleContent;

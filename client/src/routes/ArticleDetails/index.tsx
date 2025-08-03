// @ts-nocheck
/* eslint-disable */
import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Modal } from 'react-bootstrap';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate, useParams } from 'react-router-dom';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import Spinner from '@/assets/images/spinner.gif';
import CommentForm from '@/components/features/comment/CommentForm';
import CommentList from '@/components/features/comment/CommentList';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import ArticleStatus from '@/components/ui/article-status';
import { apiCall } from '@/services/api';
import { BASE_URL, HTTP_METHOD, LIST_ACTIONS, ObjectTemplates } from '@/shared/constants';
import { hideLoader, showLoader } from '@/store/actions/application';
import { setSelectedArticle } from '@/store/actions/articles';

const ArticleModalTypes = {
	SHOW_STATUS: 'showStatus',
	SHOW_DELETE: 'showDelete',
	SHOW_PDF: 'showPdf',
	SHOW_BOOKMARK: 'showBookmark',
};

const ArticleDetails: React.FC = () => {
	const [article, setArticle] = useState<any>(null);
	const [userLists, setUserLists] = useState([]);
	const [modals, setModals] = useState({
		showBookmark: false,
		showPdf: false,
		showDelete: false,
		showStatus: false,
	});
	const [loadingListIds, setLoadingListIds] = useState([]);
	const [articleTempStatus, setArticleTempStatus] = useState(null);
	const [isLoading, setIsLoading] = useState(true);
	const [isReviewLoading, setIsReviewLoading] = useState(false);
	const { article_id } = useParams();
	const navigate = useNavigate();

	const dispatch = useDispatch();

	const currentUser = useSelector((state: any) => state.currentUser);
	const selectedArticle = useSelector((state: any) => state.articles.selectedArticle);

	useEffect(() => {
		const fetchArticleDetails = async () => {
			try {
				const url = `${BASE_URL}/api/article/${article_id}`;
				// @ts-ignore
				const data = await apiCall(HTTP_METHOD.GET, url);
				const { article } = data;
				if (!article) {
					navigate('/articles');
					return;
				}
				dispatch(setSelectedArticle(article));
				setArticle(article);
				setArticleTempStatus(article.status);
			} catch (error) {
				console.error(error);
				navigate('/articles');
			} finally {
				setIsLoading(false);
			}
		};

		const fetchUserRelationsToArticle = async () => {
			try {
				// @ts-ignore
				const userLike = await apiCall(HTTP_METHOD.POST, `${BASE_URL}/api/article/${article_id}/checklike`);

				setArticle((prevArticle: any) => ({
					...prevArticle,
					isLiked: userLike.isLiked,
					comments: prevArticle.comments
						? prevArticle.comments.map((comment: any) => ({
								...comment,
								isLiked: comment.likes.some((like: any) => like.user_id === currentUser.user.id),
							}))
						: [],
				}));
			} catch (error) {
				console.log(error);
			}
		};

		const fetchUserArticleLists = async () => {
			try {
				setIsLoading(true);
				const url = `${BASE_URL}/api/user/lists/contain`;
				const data = await apiCall(HTTP_METHOD.POST, url, {
					elementId: article_id,
				});

				const articleListsContainingArticle = data.lists.filter(
					(list: any) => list.type === ObjectTemplates.ARTICLES,
				);

				setUserLists(articleListsContainingArticle);
				setArticle((prevArticle: any) => ({
					...prevArticle,
					isBookmarked: articleListsContainingArticle.length > 0,
				}));
				setIsLoading(false);
			} catch (error) {
				console.log(error);
			} finally {
				setIsLoading(false);
			}
		};

		if (!selectedArticle) {
			fetchArticleDetails();
			if (currentUser.isAuthenticated) {
				fetchUserRelationsToArticle();
				fetchUserArticleLists();
			}
		} else {
			setArticle(selectedArticle);
			if (currentUser.isAuthenticated) {
				fetchUserRelationsToArticle();
				fetchUserArticleLists();
			}
		}
	}, [article_id, currentUser, dispatch, history, selectedArticle]);

	const addToOrRemoveFromList = async (id: any, action: any) => {
		try {
			// @ts-ignore
			setLoadingListIds((prev) => [...prev, id]);

			const endpoint = action === LIST_ACTIONS.ADD_ITEM ? 'additemwhileaway' : 'removeitemwhileaway';
			const url = `${BASE_URL}/api/user/list/${endpoint}`;

			await apiCall(HTTP_METHOD.POST, url, {
				listId: id,
				elementId: article_id,
			});

			// @ts-ignore
			setUserLists((prevUserLists) =>
				prevUserLists.map((userList: any) =>
					userList.id === id
						? {
								...userList,
								elementBelongsToList: action === LIST_ACTIONS.ADD_ITEM,
							}
						: userList,
				),
			);

			// TODO: refactor to check if atleast one article is saved to use isBookmarked icon, and check after each add/remove if still have any bookmarked.
			// setArticle((prevArticle) => ({
			//   ...prevArticle,
			//   isBookmarked: action === LIST_ACTIONS.ADD_ITEM,
			// }));
		} catch (error) {
			console.error(error);
			setLoadingListIds((prev) => prev.filter((prevListId) => prevListId !== id));
		} finally {
			setLoadingListIds((prev) => prev.filter((prevListId) => prevListId !== id));
		}
	};

	const downloadPdf = async (type) => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}
		const pdfType = type === 'kanji' ? 'kanjis-pdf' : 'words-pdf';
		const loaderMessage = `Creating a ${type} PDF, please wait.`;
		dispatch(showLoader(loaderMessage) as any);
		try {
			const url = `${BASE_URL}/api/article/${article_id}/${pdfType}`;
			const res = await apiCall(HTTP_METHOD.GET, url, { responseType: 'blob' });
			const file = new Blob([res], { type: 'application/pdf' });
			const fileURL = URL.createObjectURL(file);
			window.open(fileURL);
		} catch (error) {
			console.error(error);
		} finally {
			dispatch(hideLoader() as any);
		}
	};

	const toggleModal = (modalName: any) => {
		setModals((prevModals) => ({
			...prevModals,
			// @ts-ignore
			[modalName]: !prevModals[modalName],
		}));
	};

	const handleStatusChange = async () => {
		try {
			toggleModal(ArticleModalTypes.SHOW_STATUS);
			setIsReviewLoading(true);
			const res = await apiCall(HTTP_METHOD.POST, `/api/article/${article_id}/setstatus`, {
				status: articleTempStatus,
			});
			setArticle((prevArticle: any) => ({
				...prevArticle,
				status: res.newStatus,
			}));
			setArticleTempStatus(res.newStatus);
			setIsReviewLoading(false);
		} catch (error) {
			console.error(error);
			setIsReviewLoading(false);
		}
	};

	const handleLikeArticle = async () => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}
		try {
			const endpoint = article.isLiked ? 'unlike' : 'like';
			const url = `${BASE_URL}/api/article/${article.id}/${endpoint}`;
			// @ts-ignore
			await apiCall(HTTP_METHOD.POST, url);
			setArticle((prevArticle) => ({
				...prevArticle,
				isLiked: !prevArticle.isLiked,
				likesTotal: prevArticle.likesTotal + (endpoint === 'like' ? 1 : -1),
			}));
		} catch (error) {
			console.error(error);
		}
	};

	// TODO: Explore useEffect logic to potentially avoid using 'useCallback' for less complexity
	const handleAddComment = useCallback(
		(comment: any) => {
			setArticle((prevArticle: any) => ({
				...prevArticle,
				comments: [comment, ...prevArticle.comments],
			}));
		},
		[setArticle],
	);

	const handleDeleteComment = async (commentId: any) => {
		try {
			// @ts-ignore
			await apiCall(HTTP_METHOD.DELETE, `/api/article/${article_id}/comment/${commentId}`);
			setArticle((prevArticle: any) => ({
				...prevArticle,
				comments: prevArticle.comments.filter((comment: any) => comment.id !== commentId),
			}));
		} catch (error) {
			console.error(error);
		}
	};

	const handleLikeComment = async (commentId: any) => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}
		try {
			const theComment = article.comments.find((comment: any) => comment.id === commentId);
			const endpoint = theComment.isLiked ? 'unlike' : 'like';
			const url = `${BASE_URL}/api/article/${article_id}/comment/${commentId}/${endpoint}`;
			await apiCall(HTTP_METHOD.POST, url);

			setArticle((prevArticle) => {
				const updatedComments = prevArticle.comments.map((comment: any) =>
					comment.id === commentId
						? {
								...comment,
								isLiked: !comment.isLiked,
								likesTotal: comment.likesTotal + (endpoint === 'like' ? 1 : -1),
							}
						: comment,
				);
				return { ...prevArticle, comments: updatedComments };
			});
		} catch (error) {
			console.error(error);
		}
	};

	const renderAddModal = () => {
		return (
			<Modal show={modals.showBookmark} onHide={() => toggleModal(ArticleModalTypes.SHOW_BOOKMARK)}>
				<Modal.Header closeButton>
					<Modal.Title>Choose Article List to add</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					{userLists.map((list) => {
						const isLoading = loadingListIds.includes(list.id);
						return (
							<div key={list.id} className="d-flex justify-content-between">
								<Link to={`/list/${list.id}`}>{list.title}</Link>
								{list.elementBelongsToList ? (
									<Button
										variant="danger"
										size="sm"
										onClick={() => addToOrRemoveFromList(list.id, LIST_ACTIONS.REMOVE_ITEM)}
										disabled={isLoading}
									>
										{isLoading ? (
											<span className="spinner-border spinner-border-sm"></span>
										) : (
											'Remove'
										)}
									</Button>
								) : (
									<Button
										variant="primary"
										size="sm"
										onClick={() => addToOrRemoveFromList(list.id, LIST_ACTIONS.ADD_ITEM)}
										disabled={isLoading}
									>
										{isLoading ? <span className="spinner-border spinner-border-sm"></span> : 'Add'}
									</Button>
								)}
							</div>
						);
					})}
					<small>
						{' '}
						<Link to="/newlist">Create a new list?</Link>{' '}
					</small>
				</Modal.Body>
				<Modal.Footer>
					<Button variant="secondary" onClick={() => toggleModal(ArticleModalTypes.SHOW_BOOKMARK)}>
						Close
					</Button>
				</Modal.Footer>
			</Modal>
		);
	};

	const renderPdfModal = () => {
		return (
			<Modal show={modals.showPdf} onHide={() => toggleModal(ArticleModalTypes.SHOW_PDF)}>
				<Modal.Header closeButton>
					<Modal.Title>Choose which data you want to download.</Modal.Title>
				</Modal.Header>
				<Modal.Body className="d-flex justify-content-center">
					<Button variant="ghost" onClick={() => downloadPdf('kanji')}>
						Kanji <Icon size="md" name="filePdfSolid" />
					</Button>
					<Button variant="ghost" onClick={() => downloadPdf('words')}>
						Words <Icon size="md" name="filePdfSolid" />
					</Button>
				</Modal.Body>
				<Modal.Footer>
					<Button variant="ghost" onClick={() => toggleModal(ArticleModalTypes.SHOW_PDF)}>
						Close
					</Button>
				</Modal.Footer>
			</Modal>
		);
	};

	const renderDeleteModal = () => {
		const handleDeleteArticle = async () => {
			try {
				await apiCall(HTTP_METHOD.DELETE, `/api/article/${article.id}`);
				navigate('/articles');
			} catch (error) {
				console.error(error);
			}
		};

		return (
			<Modal show={modals.showDelete} onHide={() => toggleModal(ArticleModalTypes.SHOW_DELETE)}>
				<Modal.Header closeButton>
					<Modal.Title>Are You Sure?</Modal.Title>
				</Modal.Header>
				<Modal.Footer>
					<Button variant="secondary" onClick={() => toggleModal(ArticleModalTypes.SHOW_DELETE)}>
						Cancel
					</Button>
					<Button variant="danger" onClick={handleDeleteArticle}>
						Yes, delete
					</Button>
				</Modal.Footer>
			</Modal>
		);
	};

	const renderStatusModal = () => {
		return (
			<Modal show={modals.showStatus} onHide={() => toggleModal(ArticleModalTypes.SHOW_STATUS)}>
				<Modal.Header closeButton>
					<Modal.Title>Review Article Status</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					<select
						value={articleTempStatus}
						onChange={(e) => setArticleTempStatus(parseInt(e.target.value))}
						className="form-control"
					>
						<option value={0}>Pending</option>
						<option value={1}>Review</option>
						<option value={2}>Reject</option>
						<option value={3}>Approve</option>
					</select>
				</Modal.Body>
				<Modal.Footer>
					<Button variant="secondary" onClick={() => toggleModal(ArticleModalTypes.SHOW_STATUS)}>
						Cancel
					</Button>
					<Button variant="success" onClick={handleStatusChange}>
						Submit
					</Button>
				</Modal.Footer>
			</Modal>
		);
	};

	if (isLoading) {
		return (
			<div className="container text-center">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	return (
		<div className="container">
			<div className="row justify-content-center">
				<div className="col-lg-8 ">
					<span className="row mt-4">
						<Link to="/articles" className="tag-link">
							{' '}
							<i className="fas fa-arrow-left"></i> Back
						</Link>
					</span>
					<h1 className="mt-4">{article.title_jp}</h1>
					<div className="row text-muted w-100 mb-3 justify-content-between">
						<div className="col">
							{/* TODO: use general date, transform format on frontend based on global configs */}
							Posted on {article.jp_year} {article.jp_month} {article.jp_day} {article.jp_hour}
							<br />
							<span>{article.viewsTotal} views | </span>
							{(currentUser.user.id === article.user_id || currentUser.user.isAdmin) &&
								(article.publicity === 1 ? (
									<Badge variant="primary">Public</Badge>
								) : (
									<Badge variant="secondary">Private</Badge>
								))}
							{(currentUser.user.id === article.user_id || currentUser.user.isAdmin) &&
								(isReviewLoading ? (
									<span className="spinner-border spinner-border-sm"></span>
								) : (
									<ArticleStatus status={article.status} />
								))}
						</div>
						<div>
							{currentUser.user.isAdmin && (
								<Button
									onClick={() => toggleModal(ArticleModalTypes.SHOW_STATUS)}
									variant="ghost"
									size="md"
								>
									{isReviewLoading ? (
										<span className="spinner-border spinner-border-md"></span>
									) : (
										'Review'
									)}
								</Button>
							)}

							{currentUser.user.id === article.user_id && (
								<>
									<Button
										onClick={() => toggleModal(ArticleModalTypes.SHOW_DELETE)}
										variant="ghost"
										size="md"
										hasOnlyIcon
									>
										<Icon name="trashbinSolid" size="md" />
									</Button>

									<Button
										as={Link}
										to={`/article/edit/${article.id}`}
										variant="ghost"
										size="md"
										hasOnlyIcon
									>
										<Icon size="md" name="penSolid" />
									</Button>
								</>
							)}
						</div>
					</div>

					<img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img" />
					<p className="lead">{article.content_jp}</p>
					<section className="mt-2 d-flex align-items-center flex-wrap">
						{article.hashtags.map((tag) => (
							<Chip
								className="mr-1"
								readonly
								key={tag.id + tag.content}
								title={tag.content}
								name={tag.content}
							>
								{tag.content}
							</Chip>
						))}
					</section>
					<br />
					<a href={article.source_link} target="_blank" rel="noopener noreferrer">
						original source
					</a>
					<hr />
					<div>
						<div className="mr-1 float-left d-flex">
							<img src={AvatarImg} alt="book-japanese" />
							<p className="ml-3 mt-3">created by {article.userName}</p>
						</div>
						<div className="float-right d-flex align-items-center">
							<p>{article.likesTotal} likes &nbsp;</p>
							<Button size="md" variant="ghost" hasOnlyIcon onClick={handleLikeArticle}>
								<Icon size="md" name="thumbsUpSolid" />
							</Button>
							<Button
								size="md"
								variant="ghost"
								hasOnlyIcon
								onClick={() => toggleModal(ArticleModalTypes.SHOW_BOOKMARK)}
							>
								<Icon size="md" name="bookmarkRegular" />
							</Button>
							<Button
								size="md"
								variant="ghost"
								hasOnlyIcon
								onClick={() => toggleModal(ArticleModalTypes.SHOW_PDF)}
							>
								<Icon size="md" name="filePdfSolid" />
							</Button>
						</div>
					</div>
				</div>
			</div>
			<div className="row justify-content-center">
				<div className="col-lg-8">
					<hr />
					{currentUser.isAuthenticated ? (
						<>
							<h6>Share what's on your mind</h6>
							<CommentForm
								addComment={handleAddComment}
								currentUser={currentUser}
								objectId={article.id}
								objectType="article"
							/>
						</>
					) : (
						<h6>
							You need to <Link to="/login">login</Link> to comment
						</h6>
					)}
					<CommentList
						objectId={article.id}
						currentUser={currentUser}
						comments={article.comments}
						likeComment={handleLikeComment}
						deleteComment={handleDeleteComment}
					/>
				</div>
			</div>
			{renderAddModal()}
			{renderPdfModal()}
			{renderDeleteModal()}
			{renderStatusModal()}
		</div>
	);
};

export default ArticleDetails;

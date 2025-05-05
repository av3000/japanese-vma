// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Badge, Modal } from 'react-bootstrap';
import { useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import axios from 'axios';
import classNames from 'classnames';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import Spinner from '@/assets/images/spinner.gif';
import CommentForm from '@/components/comment/CommentForm';
import CommentList from '@/components/comment/CommentList';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { apiCall } from '@/services/api';
import { BASE_URL, HTTP_METHOD } from '@/shared/constants';
import styles from './PostDetails.module.scss';

const PostDetails: React.FC = () => {
	const [post, setPost] = useState(null);
	const [showDelete, setShowDelete] = useState(false);
	const [isLoading, setIsLoading] = useState(false);

	const { post_id } = useParams();
	const navigate = useNavigate();
	const currentUser = useSelector((state) => state.currentUser);

	useEffect(() => {
		const getPostDetails = async () => {
			setIsLoading(true);
			try {
				const res = await axios.get(`${BASE_URL}/api/post/${post_id}`);
				const postData = res.data.post;

				if (!postData) {
					navigate('/community');
					return;
				}

				if (currentUser.isAuthenticated) {
					const likeRes = await apiCall(HTTP_METHOD.POST, `/api/post/${post_id}/checklike`);
					postData.isLiked = likeRes.isLiked;
				}

				if (currentUser.isAuthenticated && postData.comments) {
					postData.comments = postData.comments.map((comment) => {
						const youLikedIt = comment.likes.some((like) => like.user_id === currentUser.user.id);
						return { ...comment, isLiked: youLikedIt };
					});
				}

				setPost(postData);
			} catch (error) {
				console.error(error);
			} finally {
				setIsLoading(false);
			}
		};

		getPostDetails();
	}, [currentUser.isAuthenticated]);

	const likePost = async () => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}

		try {
			const endpoint = post.isLiked ? 'unlike' : 'like';
			const url = `${BASE_URL}/api/post/${post_id}/${endpoint}`;
			await axios.post(url);

			setPost((prevPost) => ({
				...prevPost,
				isLiked: !prevPost.isLiked,
				likesTotal: prevPost.isLiked ? prevPost.likesTotal - 1 : prevPost.likesTotal + 1,
			}));
		} catch (error) {
			console.error(error);
		}
	};

	const toggleLock = async () => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}

		if (currentUser.user.isAdmin) {
			try {
				const url = `${BASE_URL}/api/post/${post_id}/toggleLock`;
				const res = await axios.post(url);

				setPost((prevPost) => ({
					...prevPost,
					locked: res.data.locked,
				}));
			} catch (error) {
				console.error(error);
			}
		}
	};

	const handleDeleteModalClose = () => {
		setShowDelete(false);
	};

	const openDeleteModal = () => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
		} else {
			setShowDelete(true);
		}
	};

	const deletePost = async () => {
		try {
			await apiCall(HTTP_METHOD.DELETE, `/api/post/${post_id}`);
			navigate('/community');
		} catch (error) {
			console.error(error);
		}
	};

	const likeComment = async (commentId) => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}

		try {
			const comment = post.comments.find((c) => c.id === commentId);
			const endpoint = comment.isLiked ? 'unlike' : 'like';
			const url = `${BASE_URL}/api/post/${post.id}/comment/${commentId}/${endpoint}`;
			await axios.post(url);

			setPost((prevPost) => {
				const updatedComments = prevPost.comments.map((c) =>
					c.id === commentId
						? {
								...c,
								isLiked: !c.isLiked,
								likesTotal: c.isLiked ? c.likesTotal - 1 : c.likesTotal + 1,
							}
						: c,
				);
				return { ...prevPost, comments: updatedComments };
			});
		} catch (error) {
			console.error(error);
		}
	};

	const addComment = (comment) => {
		setPost((prevPost) => ({
			...prevPost,
			comments: [comment, ...prevPost.comments],
		}));
	};

	const deleteComment = async (commentId) => {
		try {
			await apiCall(HTTP_METHOD.DELETE, `/api/post/${post_id}/comment/${commentId}`);

			setPost((prevPost) => ({
				...prevPost,
				comments: prevPost.comments.filter((c) => c.id !== commentId),
			}));
		} catch (error) {
			console.error(error);
		}
	};

	const editComment = (commentId) => {
		// Implement edit comment functionality if needed
	};

	const renderPostDetails = () => {
		if (!post) return null;

		return (
			<div className="container">
				<div className="row justify-content-center">
					<div className="col-lg-8">
						<span className="row mt-4">
							<Link to="/community">Back</Link>
						</span>
						<h1 className="mt-4">{post.title}</h1>
						<div className="row text-muted w-100 mb-3 justify-content-between">
							<div className="col">
								<p className="text-muted">
									{post.created_at}
									<br />
									{post.viewsTotal} views &nbsp;
									<Badge variant="primary">{post.postType}</Badge>
								</p>
							</div>
							<div>
								{currentUser.user.isAdmin && (
									<Button onClick={() => toggleLock} variant="outline" size="md">
										<Icon name={post.locked ? 'lockSolid' : 'lockOpenSolid'} />
									</Button>
								)}

								{currentUser.user.id === post.user_id && (
									<>
										<Button onClick={openDeleteModal} variant="ghost" size="md" hasOnlyIcon>
											<Icon name="trashbinSolid" size="md" />
										</Button>

										<Button
											as={Link}
											to={`/community/edit/${post.id}`}
											variant="ghost"
											size="md"
											hasOnlyIcon
										>
											<Icon name="penSolid" size="md" />
										</Button>
									</>
								)}
							</div>
						</div>

						<p className="lead mt-5">{post.content}</p>
						<br />
						<section className="mt-2 d-flex align-items-center flex-wrap">
							{hashtags.map((tag) => (
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
						<hr />
						<div className="d-flex justify-content-between">
							<div className="d-flex align-items-center">
								<img src={AvatarImg} alt="book-japanese" />
								<p className="ml-3 mt-3">uploaded by {post.userName}</p>
							</div>
							<div className="d-flex align-items-center">
								<p
									className={classNames({
										[styles.isLiked]: post.isLiked,
									})}
								>
									{post.likesTotal} likes &nbsp;
								</p>
								<Button
									onClick={likePost}
									variant="ghost"
									className={classNames({
										[styles.isLiked]: post.isLiked,
									})}
									disabled={isLoading}
								>
									<Icon name="thumbsUpSolid" />
								</Button>
							</div>
						</div>
					</div>
				</div>
			</div>
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
			{renderPostDetails() || (
				<div className="container">
					<div className="row justify-content-center">
						<img src={Spinner} alt="spinner loading" />
					</div>
				</div>
			)}
			<br />
			<div className="row justify-content-center">
				{post && (
					<div className="col-lg-8">
						{currentUser.isAuthenticated ? (
							post.locked === 0 ? (
								<>
									<hr />
									<h6>Share what's on your mind</h6>
									<CommentForm
										addComment={addComment}
										currentUser={currentUser}
										objectId={post.id}
										objectType="post"
									/>
								</>
							) : (
								<h3>Post was locked and new comments are not allowed.</h3>
							)
						) : (
							<>
								<hr />
								<h6>
									You need to
									<Link to="/login"> login </Link>
									to comment
								</h6>
							</>
						)}
						{post.comments ? (
							<CommentList
								objectId={post.id}
								currentUser={currentUser}
								comments={post.comments}
								deleteComment={deleteComment}
								likeComment={likeComment}
								editComment={editComment}
							/>
						) : (
							<div className="container">
								<div className="row justify-content-center">
									<img src={Spinner} alt="spinner loading" />
								</div>
							</div>
						)}
					</div>
				)}
			</div>
			<Modal show={showDelete} onHide={handleDeleteModalClose}>
				<Modal.Header closeButton>
					<Modal.Title>Are You Sure?</Modal.Title>
				</Modal.Header>
				<Modal.Footer>
					<div className="col-12">
						<Button variant="secondary" className="float-left" onClick={handleDeleteModalClose}>
							Cancel
						</Button>
						<Button variant="danger" className="float-right" onClick={deletePost}>
							Yes, delete
						</Button>
					</div>
				</Modal.Footer>
			</Modal>
		</div>
	);
};

export default PostDetails;

// @ts-nocheck
/*eslint-disable */
import React, { useEffect, useState } from 'react';
import { Badge, Modal } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import classNames from 'classnames';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import Spinner from '@/assets/images/spinner.gif';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import styles from './PostDetails.module.scss';

interface Post {
	id: string | number;
	title: string;
	content: string;
	postType: string;
	user_id: string | number;
	userName: string;
	created_at: string;
	viewsTotal: number;
	likesTotal: number;
	isLiked: boolean;
	locked: number;
	hashtags: Array<{ id: number; content: string }>;
	comments: Array<any>;
}

const PostDetails: React.FC = () => {
	const [post, setPost] = useState<Post | null>(null);
	const [showDelete, setShowDelete] = useState(false);
	const [isLoading, setIsLoading] = useState(true);

	const { post_id } = useParams();
	const navigate = useNavigate();
	const { user, isAuthenticated } = useAuth();

	useEffect(() => {
		const getPostDetails = async () => {
			setIsLoading(true);
			try {
				const response = await apiCall({
					method: HttpMethod.GET,
					path: `/api/post/${post_id}`,
				});

				const postData = response.post;

				if (!postData) {
					navigate('/community');
					return;
				}

				// Check if user liked the post
				if (isAuthenticated) {
					const likeRes = await apiCall({
						method: HttpMethod.POST,
						path: `/api/post/${post_id}/checklike`,
					});
					postData.isLiked = likeRes.isLiked;
				}

				// Check if user liked comments
				if (isAuthenticated && user && postData.comments) {
					postData.comments = postData.comments.map((comment: any) => {
						const youLikedIt = comment.likes.some((like: any) => like.user_id === user.id);
						return { ...comment, isLiked: youLikedIt };
					});
				}

				setPost(postData);
			} catch (error) {
				console.error('Error fetching post:', error);
				navigate('/community');
			} finally {
				setIsLoading(false);
			}
		};

		getPostDetails();
	}, [post_id, isAuthenticated, user, navigate]);

	const likePost = async () => {
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		if (!post) return;

		try {
			const endpoint = post.isLiked ? 'unlike' : 'like';

			await apiCall({
				method: HttpMethod.POST,
				path: `/api/post/${post_id}/${endpoint}`,
			});

			setPost((prevPost) => ({
				...prevPost!,
				isLiked: !prevPost!.isLiked,
				likesTotal: prevPost!.isLiked ? prevPost!.likesTotal - 1 : prevPost!.likesTotal + 1,
			}));
		} catch (error) {
			console.error('Error liking post:', error);
		}
	};

	const toggleLock = async () => {
		if (!isAuthenticated || !user?.isAdmin) {
			return;
		}

		try {
			const response = await apiCall({
				method: HttpMethod.POST,
				path: `/api/post/${post_id}/toggleLock`,
			});

			setPost((prevPost) => ({
				...prevPost!,
				locked: response.locked,
			}));
		} catch (error) {
			console.error('Error toggling lock:', error);
		}
	};

	const deletePost = async () => {
		try {
			await apiCall({
				method: HttpMethod.DELETE,
				path: `/api/post/${post_id}`,
			});
			navigate('/community');
		} catch (error) {
			console.error('Error deleting post:', error);
		}
	};

	if (isLoading) {
		return (
			<div className="container text-center">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (!post) {
		return (
			<div className="container text-center">
				<p>Post not found</p>
			</div>
		);
	}

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
								<Badge>{post.postType}</Badge>
							</p>
						</div>

						<div>
							{user?.isAdmin && (
								<Button onClick={toggleLock} variant="outline" size="md">
									<Icon size="md" name={post.locked ? 'lockSolid' : 'lockOpenSolid'} />
								</Button>
							)}

							{user?.id === post.user_id && (
								<>
									<Button onClick={() => setShowDelete(true)} variant="ghost" size="md" hasOnlyIcon>
										<Icon name="trashbinSolid" size="md" />
									</Button>

									<Button
										// as={Link}
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
						{post.hashtags?.map((tag) => (
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
							<img src={AvatarImg} alt="avatar" />
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
							>
								<Icon size="md" name="thumbsUpSolid" />
							</Button>
						</div>
					</div>
				</div>
			</div>

			{/* Comments Section - NEW: Using CommentsBlock */}
			<div className="row justify-content-center">
				<div className="col-lg-8">
					<CommentsBlock
						objectId={post.id}
						objectType="post"
						initialComments={post.comments || []}
						isLocked={post.locked === 1}
					/>
				</div>
			</div>

			{/* Delete Modal */}
			<Modal show={showDelete} onHide={() => setShowDelete(false)}>
				<Modal.Header closeButton>
					<Modal.Title>Are You Sure?</Modal.Title>
				</Modal.Header>
				<Modal.Footer>
					<Button variant="secondary" onClick={() => setShowDelete(false)}>
						Cancel
					</Button>
					<Button variant="danger" onClick={deletePost}>
						Yes, delete
					</Button>
				</Modal.Footer>
			</Modal>
		</div>
	);
};

export default PostDetails;

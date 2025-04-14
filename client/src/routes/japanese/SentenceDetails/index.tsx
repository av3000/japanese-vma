// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { Link, useNavigate, useParams } from 'react-router-dom';
import Spinner from '@/assets/images/spinner.gif';
import CommentForm from '@/components/features/comment/CommentForm';
import CommentList from '@/components/features/comment/CommentList';
import { apiCall } from '@/services/api';
import { BASE_URL, HTTP_METHOD, LIST_ACTIONS, ObjectTemplates } from '@/shared/constants';

const SentenceDetails: React.FC = ({ currentUser }) => {
	const [sentence, setSentence] = useState({});
	const [kanjis, setKanjis] = useState([]);
	const [lists, setLists] = useState([]);
	const [showModal, setShowModal] = useState(false);
	const [sentenceIsKnown, setSentenceIsKnown] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState([]);
	const [comments, setComments] = useState([]);

	const { sentence_id } = useParams();
	const navigate = useNavigate();

	useEffect(() => {
		getSentenceDetails();
		if (currentUser.isAuthenticated) {
			getUserSentenceLists();
		}
	}, [currentUser.isAuthenticated]);

	const getSentenceDetails = async () => {
		setIsLoading(true);
		try {
			const res = await apiCall(HTTP_METHOD.GET, `${BASE_URL}/api/sentence/${sentence_id}`);
			setSentence(res);
			setKanjis(res.kanjis || []);
			const sentenceComments = res.comments || [];

			if (currentUser.isAuthenticated) {
				sentenceComments.forEach((comment) => {
					comment.isLiked = comment.likes.some((like) => like.user_id === currentUser.user.id);
				});
			}
			setComments(sentenceComments);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoading(false);
		}
	};

	const getUserSentenceLists = async () => {
		setIsLoading(true);
		try {
			const res = await apiCall(HTTP_METHOD.POST, `${BASE_URL}/api/user/lists/contain`, {
				elementId: sentence_id,
			});

			const knownLists = res.lists.filter(
				(list) => list.type === ObjectTemplates.KNOWNSENTENCES && list.elementBelongsToList,
			);
			setSentenceIsKnown(knownLists.length > 0);

			setLists(
				res.lists.filter(
					(list) => list.type === ObjectTemplates.KNOWNSENTENCES || list.type === ObjectTemplates.SENTENCES,
				),
			);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoading(false);
		}
	};

	const toggleModal = () => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
		} else {
			setShowModal(!showModal);
		}
	};

	const addToOrRemoveFromList = async (listId, action) => {
		setLoadingListIds((prev) => [...prev, listId]);
		try {
			const endpoint = action === LIST_ACTIONS.ADD_ITEM ? 'additemwhileaway' : 'removeitemwhileaway';
			const url = `${BASE_URL}/api/user/list/${endpoint}`;

			await apiCall(HTTP_METHOD.POST, url, {
				listId,
				elementId: sentence_id,
			});

			setLists((prevLists) =>
				prevLists.map((list) =>
					list.id === listId
						? {
								...list,
								elementBelongsToList: action === LIST_ACTIONS.ADD_ITEM,
							}
						: list,
				),
			);

			if (action === LIST_ACTIONS.ADD_ITEM) {
				if (lists.find((list) => list.id === listId && list.type === ObjectTemplates.KNOWNSENTENCES)) {
					setSentenceIsKnown(true);
				}
			} else {
				const stillKnown = lists.some(
					(list) =>
						list.type === ObjectTemplates.KNOWNSENTENCES && list.elementBelongsToList && list.id !== listId,
				);
				setSentenceIsKnown(stillKnown);
			}
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== listId));
		}
	};

	const likeComment = async (commentId) => {
		if (!currentUser.isAuthenticated) {
			navigate('/login');
			return;
		}
		try {
			const comment = comments.find((comment) => comment.id === commentId);
			const endpoint = comment.isLiked ? 'unlike' : 'like';
			const url = `${BASE_URL}/api/sentence/${sentence_id}/comment/${commentId}/${endpoint}`;

			await apiCall(HTTP_METHOD.POST, url);

			setComments((prevComments) =>
				prevComments.map((c) =>
					c.id === commentId
						? {
								...c,
								isLiked: !c.isLiked,
								likesTotal: c.isLiked ? c.likesTotal - 1 : c.likesTotal + 1,
							}
						: c,
				),
			);
		} catch (error) {
			console.error(error);
		}
	};

	const addComment = (comment) => {
		setComments((prevComments) => [comment, ...prevComments]);
	};

	const deleteComment = async (commentId) => {
		try {
			await apiCall(HTTP_METHOD.DELETE, `/api/sentence/${sentence_id}/comment/${commentId}`);
			setComments((prevComments) => prevComments.filter((comment) => comment.id !== commentId));
		} catch (error) {
			console.error(error);
		}
	};

	const editComment = (commentId) => {
		// Implement edit functionality if needed
	};

	const renderSentenceDetails = () => {
		return (
			<div className="row justify-content-center mt-5">
				<div className="col-md-8">
					<h4>{sentence.content}</h4>
					{sentence.user_id ? (
						<p>User Author - {sentence.user_id}</p>
					) : (
						<p>
							Tatoeba link:{' '}
							<a
								href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`}
								target="_blank"
								rel="noopener noreferrer"
							>
								{sentence.tatoeba_entry}
							</a>
						</p>
					)}
				</div>
				<div className="col-md-4">
					{sentenceIsKnown && <i className="fas fa-check-circle text-success"> Learned</i>}
					<button
						onClick={toggleModal}
						className="btn btn-outline brand-button float-right"
						variant="outline-primary"
					>
						<i className="far fa-bookmark fa-lg"></i>
					</button>
				</div>
			</div>
		);
	};

	const renderKanjiList = () => {
		return (
			<>
				<h4>Kanjis ({kanjis.length}) results</h4>
				<div className="container">
					{kanjis.map((kanji) => {
						const meanings = kanji.meaning.split('|').slice(0, 3).join(', ');
						return (
							<div className="row justify-content-center mt-5" key={kanji.id}>
								<div className="col-md-10">
									<div className="row">
										<div className="col-md-6">
											<h3>{kanji.kanji}</h3>
										</div>
										<div className="col-md-4">{meanings}</div>
										<div className="col-md-2">
											<Link to={`/api/kanji/${kanji.id}`} className="float-right">
												<i className="fas fa-external-link-alt fa-lg"></i>
											</Link>
										</div>
									</div>
									<hr />
								</div>
							</div>
						);
					})}
				</div>
			</>
		);
	};

	const renderAddModal = () => {
		return (
			<Modal show={showModal} onHide={toggleModal}>
				<Modal.Header closeButton>
					<Modal.Title>Choose Sentence List to add</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					{lists.map((list) => {
						const isLoadingList = loadingListIds.includes(list.id);
						return (
							<div key={list.id} className="d-flex justify-content-between mb-2">
								<Link to={`/list/${list.id}`}>{list.title}</Link>
								<Button
									variant={list.elementBelongsToList ? 'danger' : 'primary'}
									size="sm"
									onClick={() =>
										addToOrRemoveFromList(
											list.id,
											list.elementBelongsToList
												? LIST_ACTIONS.REMOVE_ITEM
												: LIST_ACTIONS.ADD_ITEM,
										)
									}
									disabled={isLoadingList}
								>
									{isLoadingList ? (
										<span className="spinner-border spinner-border-sm"></span>
									) : list.elementBelongsToList ? (
										'Remove'
									) : (
										'Add'
									)}
								</Button>
							</div>
						);
					})}
					<small>
						<Link to="/newlist">Create a new list?</Link>
					</small>
				</Modal.Body>
				<Modal.Footer>
					<Button variant="secondary" onClick={toggleModal}>
						Close
					</Button>
				</Modal.Footer>
			</Modal>
		);
	};

	if (isLoading) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			</div>
		);
	}

	return (
		<div className="container">
			<span className="mt-4">
				<Link to="/sentences" className="tag-link">
					Back
				</Link>
			</span>
			{renderSentenceDetails()}
			<hr />
			{renderKanjiList()}
			<hr />
			<br />
			<div className="row justify-content-center">
				{currentUser.isAuthenticated ? (
					<div className="col-lg-8">
						<hr />
						<h6>Share what's on your mind</h6>
						<CommentForm
							addComment={addComment}
							currentUser={currentUser}
							objectId={sentence.id}
							objectType="sentence"
						/>
					</div>
				) : (
					<div className="col-lg-8">
						<hr />
						<h6>
							You need to
							<Link to="/login"> login </Link>
							to comment
						</h6>
					</div>
				)}
				<div className="col-lg-8">
					{comments.length ? (
						<CommentList
							objectId={sentence.id}
							currentUser={currentUser}
							comments={comments}
							deleteComment={deleteComment}
							likeComment={likeComment}
							editComment={editComment}
						/>
					) : (
						<div className="container">
							<div className="row justify-content-center">
								<p>No comments yet.</p>
							</div>
						</div>
					)}
				</div>
			</div>
			{renderAddModal()}
		</div>
	);
};

export default SentenceDetails;

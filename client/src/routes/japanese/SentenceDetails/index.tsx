// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
	applyCatalogueForItemAction,
	type CatalogueForItem,
	type CatalogueForItemAction,
	fetchCataloguesForItem,
	updateCatalogueForItem,
} from '@/api/catalogues/cataloguesForItem';
import Spinner from '@/assets/images/spinner.gif';
import CommentForm from '@/components/features/comment/CommentsBlock/CommentForm/CommentForm';
import CommentList from '@/components/features/comment/CommentsBlock/CommentList/CommentList';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { BASE_URL, ObjectTemplates } from '@/shared/constants';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { HttpMethod } from '@/shared/types';

const SentenceDetails: React.FC = () => {
	const [sentence, setSentence] = useState({});
	const [kanjis, setKanjis] = useState([]);
	const [lists, setLists] = useState([]);
	const [showModal, setShowModal] = useState(false);
	const [sentenceIsKnown, setSentenceIsKnown] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState([]);
	const [comments, setComments] = useState([]);

	const { sentence_id } = useParams();
	const entityId = Number(sentence_id);
	const navigate = useNavigate();
	const { user: currentUser, isAuthenticated } = useAuth();

	useEffect(() => {
		getSentenceDetails();
		if (isAuthenticated) {
			getUserSentenceLists();
		}
	}, [isAuthenticated]);

	const getSentenceDetails = async () => {
		setIsLoading(true);
		try {
			const res = await apiCall(HttpMethod.GET, `${BASE_URL}/api/sentence/${sentence_id}`);
			setSentence(res);
			setKanjis(res.kanjis || []);
			const sentenceComments = res.comments || [];

			if (isAuthenticated) {
				sentenceComments.forEach((comment) => {
					comment.isLiked = comment.likes.some((like) => like.user_id === currentUser.id);
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
			const nextLists = await fetchCataloguesForItem(sentence_id, {
				types: [ObjectTemplates.KNOWNSENTENCES, ObjectTemplates.SENTENCES],
			});
			setSentenceIsKnown(
				nextLists.some((list) => list.type === ObjectTemplates.KNOWNSENTENCES && list.contains_item),
			);
			setLists(nextLists);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoading(false);
		}
	};

	const toggleModal = () => {
		if (!isAuthenticated) {
			navigate('/login');
		} else {
			setShowModal(!showModal);
		}
	};

	const addToOrRemoveFromList = async (list: CatalogueForItem, action: CatalogueForItemAction) => {
		if (Number.isNaN(entityId)) return;

		setLoadingListIds((prev) => [...prev, list.id]);
		try {
			await updateCatalogueForItem({
				list,
				elementId: entityId,
				action,
			});

			setLists((prevLists) => {
				const nextLists = applyCatalogueForItemAction(prevLists, list.id, action);
				setSentenceIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNSENTENCES && list.contains_item),
				);
				return nextLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
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
								<Link to={CATALOGUE_ROUTES.detail(list.uuid)}>{list.title}</Link>
								<Button
									variant={list.contains_item ? 'danger' : 'primary'}
									size="sm"
									onClick={() => addToOrRemoveFromList(list, list.contains_item ? 'remove' : 'add')}
									disabled={isLoadingList}
								>
									{isLoadingList ? (
										<span className="spinner-border spinner-border-sm"></span>
									) : list.contains_item ? (
										'Remove'
									) : (
										'Add'
									)}
								</Button>
							</div>
						);
					})}
					<small>
						<Link to={CATALOGUE_ROUTES.create}>Create a new list?</Link>
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
				<div className="col-lg-8">
					<CommentsBlock
						objectId={sentence.id}
						objectType="sentence"
						initialComments={sentence.comments || []}
					/>
				</div>
			</div>
			{renderAddModal()}
		</div>
	);
};

export default SentenceDetails;

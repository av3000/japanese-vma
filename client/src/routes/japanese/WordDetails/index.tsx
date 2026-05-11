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
import { Chip } from '@/components/shared/Chip';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { BASE_URL, ObjectTemplates } from '@/shared/constants';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { HttpMethod } from '@/shared/types';

const WordDetails: React.FC = () => {
	const [word, setWord] = useState({});
	const [kanjis, setKanjis] = useState([]);
	const [articles, setArticles] = useState([]);
	const [lists, setLists] = useState([]);
	const [showModal, setShowModal] = useState(false);
	const [wordIsKnown, setWordIsKnown] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState([]);

	const { word_id } = useParams();
	const entityId = Number(word_id);
	const navigate = useNavigate();

	const { isAuthenticated } = useAuth();

	useEffect(() => {
		const getWordDetails = async () => {
			try {
				setIsLoading(true);
				const res = await apiCall({ method: HttpMethod.GET, path: `${BASE_URL}/word/${word_id}` });

				const processedWord = {
					...res,
					meaning: res.meaning.split('|').join(', '),
				};

				setWord(processedWord);
				setKanjis(res.kanjis.data || []);
				setArticles(res.articles.data || []);
			} catch (error) {
				console.error(error);
			} finally {
				setIsLoading(false);
			}
		};

		const getUserWordLists = async () => {
			try {
				setIsLoading(true);
				const nextLists = await fetchCataloguesForItem(word_id, {
					types: [ObjectTemplates.KNOWNWORDS, ObjectTemplates.WORDS],
				});
				setWordIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNWORDS && list.contains_item),
				);
				setLists(nextLists);
			} catch (error) {
				console.error(error);
			} finally {
				setIsLoading(false);
			}
		};

		getWordDetails();
		if (isAuthenticated) {
			getUserWordLists();
		}
	}, [isAuthenticated]);

	const toggleModal = () => {
		if (!isAuthenticated) {
			navigate('/login');
		} else {
			setShowModal((prevShow) => !prevShow);
		}
	};

	const addToOrRemoveFromList = async (list: CatalogueForItem, action: CatalogueForItemAction) => {
		if (Number.isNaN(entityId)) return;

		try {
			setLoadingListIds((prev) => [...prev, list.id]);
			await updateCatalogueForItem({
				list,
				elementId: entityId,
				action,
			});

			setLists((prevLists) => {
				const nextLists = applyCatalogueForItemAction(prevLists, list.id, action);
				setWordIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNWORDS && list.contains_item),
				);
				return nextLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
	};

	const renderWordDetails = () => {
		return (
			<div className="row justify-content-center mt-5">
				<div className="col-md-4">
					<h1>{word.word}</h1>
					<p>Furigana: {word.furigana}</p>
				</div>
				<div className="col-md-4">
					<p>Type: {word.word_type}</p>
				</div>
				<div className="col-md-4">
					<p>
						JLPT: {word.jlpt} <br /> Meaning: {word.meaning}
					</p>
					{wordIsKnown && <i className="fas fa-check-circle text-success"> Learned</i>}
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
											<Link to={`/kanji/${kanji.id}`} className="float-right">
												Open
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

	const renderArticleList = () => {
		return (
			<>
				<h4>Articles ({articles.length}) results</h4>
				<div className="container">
					{articles.map((article) => (
						<div className="row justify-content-center mt-5" key={article.id}>
							<div className="col-md-12">
								<div className="row">
									<div className="col-md-8">
										<h3>{article.title_jp}</h3>
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
									</div>
									<div className="col-md-2">
										<p>
											Views: {article.viewsTotal} <br />
											Likes: {article.likesTotal} <br />
											Comments: {article.commentsTotal}
										</p>
									</div>
									<div className="col-md-2">
										<Link to={`/article/${article.id}`} className="float-right" target="_blank">
											Open
										</Link>
									</div>
								</div>
								<hr />
							</div>
						</div>
					))}
				</div>
			</>
		);
	};

	const renderAddModal = () => {
		return (
			<Modal show={showModal} onHide={toggleModal}>
				<Modal.Header closeButton>
					<Modal.Title>Choose Word List to add</Modal.Title>
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
				<Link to="/words" className="tag-link">
					Back
				</Link>
			</span>
			{renderWordDetails()}
			<hr />
			{renderKanjiList()}
			<hr />
			{renderArticleList()}
			{renderAddModal()}
		</div>
	);
};

export default WordDetails;

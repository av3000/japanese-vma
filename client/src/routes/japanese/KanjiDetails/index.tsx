// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Modal } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import {
	applyCatalogueForItemAction,
	type CatalogueForItem,
	type CatalogueForItemAction,
	fetchCataloguesForItem,
	updateCatalogueForItem,
} from '@/api/catalogues/cataloguesForItem';
import Spinner from '@/assets/images/spinner.gif';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { BASE_URL, ObjectTemplates } from '@/shared/constants';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { HttpMethod } from '@/shared/types';

const KanjiOpen: React.FC = () => {
	const [kanji, setKanji] = useState({});
	const [words, setWords] = useState([]);
	const [sentences, setSentences] = useState([]);
	const [articles, setArticles] = useState([]);
	const [lists, setLists] = useState([]);
	const [showModal, setShowModal] = useState(false);
	const [kanjiIsKnown, setKanjiIsKnown] = useState(false);
	const [isLoading, setIsLoading] = useState(true);
	const [loadingListIds, setLoadingListIds] = useState([]);

	const { kanji_id } = useParams();
	const entityId = Number(kanji_id);
	const navigate = useNavigate();
	const { isAuthenticated } = useAuth();

	useEffect(() => {
		getKanjiOpen();
		if (isAuthenticated) {
			getUserKanjiLists();
		}
	}, [isAuthenticated]);

	const getKanjiOpen = async () => {
		try {
			setIsLoading(true);
			const res = await apiCall({ method: HttpMethod.GET, path: `${BASE_URL}/kanji/${kanji_id}` });

			// Process the kanji data
			// TODO: Should handle data materialization on backend and return necessary formatted data.
			const processedKanji = {
				...res,
				meaning: res.meaning.split('|').join(', '),
				onyomi: res.onyomi.split('|').join(', '),
				kunyomi: res.kunyomi.split('|').join(', '),
			};
			console.log('getKanjiData: ', res.articles.data);
			setKanji(processedKanji);
			setWords(res.words.data || []);
			setSentences(res.sentences.data || []);
			setArticles(res.articles.data || []);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoading(false);
		}
	};

	const getUserKanjiLists = async () => {
		try {
			setIsLoading(true);
			const nextLists = await fetchCataloguesForItem(kanji_id, {
				types: [ObjectTemplates.KNOWNKANJIS, ObjectTemplates.KANJIS],
			});
			setKanjiIsKnown(nextLists.some((list) => list.type === ObjectTemplates.KNOWNKANJIS && list.contains_item));
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
				setKanjiIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNKANJIS && list.contains_item),
				);
				return nextLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
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

	const renderKanjiOpen = () => {
		return (
			<div className="row justify-content-center mt-5">
				<div className="col-md-4">
					<h1>
						{kanji.kanji} <br />
						{kanji.hiragana}
					</h1>
					<p>Meaning: {kanji.meaning}</p>
				</div>
				<div className="col-md-4">
					<p>Onyomi: {kanji.onyomi}</p>
					<p>Kunyomi: {kanji.kunyomi}</p>
				</div>
				<div className="col-md-2">
					<p>Parts: {kanji.radical_parts}</p>
					<p>Strokes: {kanji.stroke_count}</p>
				</div>
				<div className="col-md-2">
					<p>JLPT: {kanji.jlpt}</p>
					<p>Frequency: {kanji.frequency}</p>
					{kanjiIsKnown && <i className="fas fa-check-circle text-success"> Learned</i>}
					<Button size="md" onClick={toggleModal} variant="outline">
						<Icon size="sm" name="bookmarkRegular" />
					</Button>
				</div>
			</div>
		);
	};

	const renderWordsList = () => {
		return (
			<>
				<h4>Found in ({words.length}) words</h4>
				<div className="container">
					{words.map((word) => {
						const meanings = word.meaning.split('|').slice(0, 3).join(', ');
						return (
							<div className="row justify-content-center mt-5" key={word.id}>
								<div className="col-md-10">
									<div className="row">
										<div className="col-md-6">
											<h3>{word.word}</h3>
										</div>
										<div className="col-md-4">{meanings}</div>
										<div className="col-md-2">
											<Link to={`/word/${word.id}`} className="float-right">
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

	const renderSentenceList = () => {
		return (
			<>
				<h4>Found in ({sentences.length}) sentences</h4>
				<div className="container">
					{sentences.map((sentence) => (
						<div className="row justify-content-center mt-5" key={sentence.id}>
							<div className="col-md-12">
								<div className="row">
									<div className="col-md-12">
										<h3>{sentence.content}</h3>
									</div>
									<Button to={`/sentence/${sentence.id}`} size="md" variant="outline">
										Open
									</Button>
									{sentence.tatoeba_entry ? (
										<Button
											variant="link"
											size="md"
											href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`}
											target="_blank"
											rel="noopener noreferrer"
										>
											Tatoeba <Icon name="externalLink" size="sm" />
										</Button>
									) : (
										<Button type="button" size="sm" variant="secondary" disabled>
											Local
										</Button>
									)}
								</div>
								<hr />
							</div>
						</div>
					))}
				</div>
			</>
		);
	};

	const renderArticleList = () => {
		return (
			<>
				<h4>Found in ({articles.length}) articles</h4>
				<div className="container">
					{articles.map((article) => (
						<div className="row justify-content-center mt-5" key={article.id}>
							<div className="col-md-12">
								<div className="row">
									<div className="col-md-8">
										<h3>{article.title_jp}</h3>
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
									</div>
									<div className="col-md-2">
										<p>
											Views: {article.viewsTotal} <br />
											Likes: {article.likesTotal} <br />
											Comments: {article.commentsTotal} <br />
										</p>
									</div>
									<div className="col-md-2">
										<Button
											variant="link"
											size="md"
											to={`/article/${article.id}`}
											target="_blank"
											rel="noopener noreferrer"
										>
											Tatoeba <Icon name="externalLink" size="sm" />
										</Button>
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
					<Modal.Title>Choose Kanji List to add</Modal.Title>
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
									isLoading={isLoadingList}
									onClick={() => addToOrRemoveFromList(list, list.contains_item ? 'remove' : 'add')}
									disabled={isLoadingList}
								>
									{list.contains_item ? 'Remove' : 'Add'}
								</Button>
							</div>
						);
					})}
					<small>
						<Link to={CATALOGUE_ROUTES.create}>Create a new list?</Link>
					</small>
				</Modal.Body>
				<Modal.Footer>
					<Button type="button" size="md" variant="secondary" onClick={toggleModal}>
						Close
					</Button>
				</Modal.Footer>
			</Modal>
		);
	};

	return (
		<div className="container">
			<div className="mt-4">
				<Link to="/kanjis" className="tag-link">
					Back
				</Link>
			</div>
			{!isLoading && (
				<>
					{renderKanjiOpen()}
					<hr />
					{renderWordsList()}
					{renderSentenceList()}
					{renderArticleList()}
					{renderAddModal()}
				</>
			)}
		</div>
	);
};

export default KanjiOpen;

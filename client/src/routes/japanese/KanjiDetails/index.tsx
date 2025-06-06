// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Modal } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { apiCall } from '@/services/api';
import { BASE_URL, HTTP_METHOD, LIST_ACTIONS, ObjectTemplates } from '@/shared/constants';
import Spinner from '../../assets/images/spinner.gif';
import Hashtags from '../ui/hashtags';

const KanjiOpen: React.FC = ({ currentUser }) => {
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
	const navigate = useNavigate();

	useEffect(() => {
		getKanjiOpen();
		if (currentUser.isAuthenticated) {
			getUserKanjiLists();
		}
	}, [currentUser.isAuthenticated]);

	const getKanjiOpen = async () => {
		try {
			setIsLoading(true);
			const res = await apiCall(HTTP_METHOD.GET, `${BASE_URL}/api/kanji/${kanji_id}`);

			// Process the kanji data
			const processedKanji = {
				...res,
				meaning: res.meaning.split('|').join(', '),
				onyomi: res.onyomi.split('|').join(', '),
				kunyomi: res.kunyomi.split('|').join(', '),
			};

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
			const res = await apiCall(HTTP_METHOD.POST, `${BASE_URL}/api/user/lists/contain`, {
				elementId: kanji_id,
			});

			const knownLists = res.lists.filter(
				(list) => list.type === ObjectTemplates.KNOWNKANJIS && list.elementBelongsToList,
			);
			setKanjiIsKnown(knownLists.length > 0);

			setLists(
				res.lists.filter(
					(list) => list.type === ObjectTemplates.KNOWNKANJIS || list.type === ObjectTemplates.KANJIS,
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
			setShowModal((prevShow) => !prevShow);
		}
	};

	const addToOrRemoveFromList = async (listId, action) => {
		try {
			setLoadingListIds((prev) => [...prev, listId]);

			const endpoint = action === LIST_ACTIONS.ADD_ITEM ? 'additemwhileaway' : 'removeitemwhileaway';
			const url = `${BASE_URL}/api/user/list/${endpoint}`;

			await apiCall(HTTP_METHOD.POST, url, {
				listId,
				elementId: kanji_id,
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

			// Update kanjiIsKnown if the known kanji list is modified
			if (action === LIST_ACTIONS.ADD_ITEM) {
				if (lists.find((list) => list.id === listId && list.type === ObjectTemplates.KNOWNKANJIS)) {
					setKanjiIsKnown(true);
				}
			} else {
				const stillKnown = lists.some(
					(list) =>
						list.type === ObjectTemplates.KNOWNKANJIS && list.elementBelongsToList && list.id !== listId,
				);
				setKanjiIsKnown(stillKnown);
			}
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== listId));
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
								<Link to={`/list/${list.id}`}>{list.title}</Link>
								<Button
									variant={list.elementBelongsToList ? 'danger' : 'primary'}
									size="sm"
									isLoading={isLoadingList}
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
									{list.elementBelongsToList ? 'Remove' : 'Add'}
								</Button>
							</div>
						);
					})}
					<small>
						<Link to="/newlist">Create a new list?</Link>
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
			{renderKanjiOpen()}
			<hr />
			{renderWordsList()}
			{renderSentenceList()}
			{renderArticleList()}
			{renderAddModal()}
		</div>
	);
};

export default KanjiOpen;

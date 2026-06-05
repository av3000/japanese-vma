// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import Spinner from '@/assets/images/spinner.gif';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { BASE_URL } from '@/shared/constants';
import { SavedListType } from '@/shared/constants/enums';
import { HttpMethod } from '@/shared/types';

const KanjiOpen: React.FC = () => {
	const [kanji, setKanji] = useState({});
	const [words, setWords] = useState([]);
	const [sentences, setSentences] = useState([]);
	const [articles, setArticles] = useState([]);
	const [isLoading, setIsLoading] = useState(true);

	const { kanji_id } = useParams();
	const entityId = Number(kanji_id);
	const { isAuthenticated } = useAuth();

	useEffect(() => {
		getKanjiOpen();
	}, [isAuthenticated]);

	const getKanjiOpen = async () => {
		try {
			setIsLoading(true);
			const res = await apiCall({ method: HttpMethod.GET, path: `${BASE_URL}/kanji/${kanji_id}` });

			// Process the kanji data
			// TODO: Should handle data materialization on backend or on client API mapping backend data to frontend and return necessary formatted data.
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
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.KANJIS}
							isKnownType={SavedListType.KNOWNKANJIS}
							entityId={entityId}
							modalTitle="Choose Kanji List to add"
						/>
					)}
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
											<p>{word.furigana}</p>
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
				</>
			)}
		</div>
	);
};

export default KanjiOpen;

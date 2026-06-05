// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import Spinner from '@/assets/images/spinner.gif';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Chip } from '@/components/shared/Chip';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { BASE_URL } from '@/shared/constants';
import { SavedListType } from '@/shared/constants/enums';
import { HttpMethod } from '@/shared/types';

const WordDetails: React.FC = () => {
	const [word, setWord] = useState({});
	const [kanjis, setKanjis] = useState([]);
	const [articles, setArticles] = useState([]);
	const [isLoading, setIsLoading] = useState(false);

	const { word_id } = useParams();
	const entityId = Number(word_id);

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

		getWordDetails();
	}, [isAuthenticated]);

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
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.WORDS}
							isKnownType={SavedListType.KNOWNWORDS}
							entityId={entityId}
							modalTitle="Choose Word List to add"
						/>
					)}
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

	// TODO: move to japanese material related component, to reuse in any japanese material page
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
		</div>
	);
};

export default WordDetails;

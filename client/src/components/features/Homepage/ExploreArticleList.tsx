// @ts-nocheck
/* eslint-disable */
import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import Spinner from '@/assets/images/spinner.gif';
import { fetchArticles } from '@/store/slices/articlesSlice';
import ArticleItem from '../article/ArticleItem';

const ExploreArticleList: React.FC = () => {
	const dispatch = useDispatch();
	const articles = useSelector((state) => state.articles.all);
	const isLoading = useSelector((state) => state.articles.loading);
	const paginationInfo = useSelector((state) => state.articles.paginationInfo);

	useEffect(() => {
		if (!articles.length) {
			// TODO: Either create loader on the route, or pass pagination parameters.
			dispatch(fetchArticles());
		}
	}, [dispatch, articles.length]);

	if (isLoading) {
		return (
			<div className="d-flex justify-content-center w-100">
				<img src={Spinner} alt="spinner loading" />
			</div>
		);
	}

	return (
		<>
			<div className="d-flex justify-content-between align-items-center w-100 my-3">
				<h3>Latest Articles ({paginationInfo.total || 0})</h3>
				<div>
					<Link to="/articles" className="homepage-section-title">
						Read All Articles
					</Link>
				</div>
			</div>
			<div className="row">
				{articles.map((a) => (
					<ArticleItem key={a.id} {...a} />
				))}
			</div>
		</>
	);
};

export default ExploreArticleList;

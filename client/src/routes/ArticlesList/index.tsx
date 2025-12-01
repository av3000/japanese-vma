// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import Spinner from '@/assets/images/spinner.gif';
import SearchBar from '@/components/features/SearchBar';
import ArticleItem from '@/components/features/article/ArticleItem';
import { Button } from '@/components/shared/Button';
import { fetchArticles, setSelectedArticle } from '@/store/slices/articlesSlice';

const ArticleList: React.FC = () => {
	const dispatch = useDispatch();
	const articles = useSelector((state) => state.articles.all);
	const [displayArticles, setDisplayArticles] = useState([]);
	const isLoading = useSelector((state: any) => state.articles.loading);
	const paginationInfo = useSelector((state: any) => state.articles.paginationInfo);

	const [searchState, setSearchState] = useState({
		searchHeading: '',
		searchTotal: '',
		filters: {},
	});

	const { searchHeading, searchTotal, filters } = searchState as any;

	// TODO: Analyse useEffect and data fetching methods for exploring ways to changing Architecture without useEffect for data fetching.
	useEffect(() => {
		if (!articles.length) {
			dispatch(fetchArticles() as any);
		}
	}, [dispatch, articles.length]);

	useEffect(() => {
		if (paginationInfo.total) {
			const searchHeading = filters.title ? `Results for: ${filters.title}` : '';
			const searchTotal = `Results total: ${paginationInfo.total}`;
			setSearchState((prev) => ({ ...prev, searchHeading, searchTotal }));
		}
	}, [paginationInfo.total, filters.title]);

	const applyFilters = (filters: any) => {
		setSearchState((prev) => ({ ...prev, filters }));
		dispatch(fetchArticles(filters) as any);
	};

	const loadMore = () => {
		if (paginationInfo.next_page_url) {
			const page = paginationInfo.current_page + 1;
			const newFilters = { ...filters, page };
			dispatch(fetchArticles(newFilters) as any);
		}
	};

	const handleSetSelectedArticle = (article: any) => {
		dispatch(setSelectedArticle(article));
	};

	const articleList = articles.length ? (
		<>
			{articles.map((a) => (
				<ArticleItem key={a.id} {...a} onClick={() => handleSetSelectedArticle(a)} />
			))}
		</>
	) : (
		<div className="container">
			<div className="row justify-content-center">
				<p>No articles found.</p>
			</div>
		</div>
	);

	return (
		<div className="container">
			<SearchBar fetchQuery={applyFilters} searchType="articles" />
			{searchHeading && <h4>{searchHeading}</h4>}
			{searchTotal && <h4>{searchTotal}</h4>}

			<div>
				<div>
					{articles.length} of total {paginationInfo.total}
				</div>
				<div className="row">{articleList}</div>
			</div>

			{isLoading ? (
				<div className="row justify-content-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			) : (
				<div className="container">
					<div className="row justify-content-center">
						{paginationInfo.current_page === paginationInfo.last_page || !paginationInfo.next_page_url ? (
							'No more results...'
						) : (
							<Button variant="secondary-outline" className="w-50" onClick={loadMore}>
								{articles.length}
								Load More
							</Button>
						)}
					</div>
				</div>
			)}
		</div>
	);
};

export default ArticleList;

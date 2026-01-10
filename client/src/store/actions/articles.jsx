import { HttpMethod } from '@/shared/types';
import { apiCall } from '../../services/api';
import { LOAD_ARTICLES, SET_LOADING, SET_PAGINATION_INFO, REMOVE_ARTICLE, SET_SELECTED_ARTICLE } from '../actionTypes';
import { addError } from './errors';

export const loadArticles = (articles) => ({
	type: LOAD_ARTICLES,
	articles,
});

export const remove = (id) => ({
	type: REMOVE_ARTICLE,
	id,
});

export const setSelectedArticle = (article) => ({
	type: SET_SELECTED_ARTICLE,
	article,
});

export const setLoading = (isLoading) => ({
	type: SET_LOADING,
	isLoading,
});

export const setPaginationInfo = (paginationInfo) => ({
	type: SET_PAGINATION_INFO,
	paginationInfo,
});

export const fetchArticles = (filters = {}) => {
	return async (dispatch) => {
		try {
			dispatch(setLoading(true));
			const queryParams = new URLSearchParams(filters).toString();
			const url = `/api/articles${queryParams ? `?${queryParams}` : ''}`;
			const res = await apiCall(HttpMethod.GET, url);
			dispatch(loadArticles(res.articles));
			dispatch(
				setPaginationInfo({
					current_page: res.articles.current_page,
					last_page: res.articles.last_page,
					next_page_url: res.articles.next_page_url,
					prev_page_url: res.articles.prev_page_url,
					total: res.articles.total,
				}),
			);
			dispatch(setLoading(false));
		} catch (err) {
			console.log(err);
			dispatch(addError(err.message));
			dispatch(setLoading(false));
		}
	};
};

export const removeArticle = (article_id) => {
	return (dispatch) => {
		return apiCall(HttpMethod.DELETE, `/api/article/${article_id}`)
			.then(() => {
				dispatch(remove(article_id));
			})
			.catch((err) => {
				dispatch(addError(err.message));
			});
	};
};

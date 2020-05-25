import { apiCall } from '../../services/api';
import { addError } from './errors';
import { LOAD_ARTICLES, REMOVE_ARTICLE } from '../actionTypes';

export const loadArticles = articles => ({
    type: LOAD_ARTICLES,
    articles
});

export const fetchArticles = () => {
    return dispatch => {
        return apiCall("get", '/api/articles')
            .then(res => {
                console.log("apiCall returned something");
                console.log(res);
                dispatch(loadArticles(res));
            })
            .catch(err => {
                console.log(err);
                // addError(err.message);
            })
    };
};
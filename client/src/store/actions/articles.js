import { apiCall } from '../../services/api';
import { addError } from './errors';
import { LOAD_ARTICLES, REMOVE_ARTICLE } from '../actionTypes';

export const loadArticles = articles => ({
    type: LOAD_ARTICLES,
    articles
});

export const remove = id => ({
    type: REMOVE_ARTICLE,
    id
});

export const fetchArticles = () => {
    return dispatch => {
        return apiCall("get", '/api/articles')
            .then(res => {
                dispatch(loadArticles(res));
            })
            .catch(err => {
                console.log(err);
                // addError(err.message);
            })
    };
};

export const removeArticle = (article_id) => {
    return dispatch => {
      return apiCall("delete", `/api/article/${article_id}`)
        .then(res =>  {
            dispatch(remove(article_id)) 
        })
        .catch(err => {
            addError(err.message);
        });
    };
  };


  export const postNewArticle = article => (dispatch, getState) => {
    let { currentUser } = getState();
    return apiCall("post", `/api/article`, { article })
      .then(res => {})
      .catch(err => addError(err));
  };
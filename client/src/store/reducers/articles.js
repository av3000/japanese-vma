import { LOAD_ARTICLES, REMOVE_ARTICLE } from '../actionTypes';

const article = (state = [], action) => {
    switch(action.type) {
        case LOAD_ARTICLES:
            console.log("LOAD_ARTICLES");
            console.log(action.articles.articles);
            return [...action.articles.articles];
        default:
            return state;
    };
};

export default article;
import {
  LOAD_ARTICLES,
  SET_SELECTED_ARTICLE,
  SET_LOADING,
  SET_PAGINATION_INFO,
} from "../actionTypes";

const initialState = {
  articles: [],
  selectedArticle: null,
  paginationInfo: {},
  isLoading: false,
};

const articleReducer = (state = initialState, action) => {
  switch (action.type) {
    case LOAD_ARTICLES:
      const isPaginatedLoad = action.articles.current_page > 1;
      return {
        ...state,
        articles: isPaginatedLoad
          ? [...state.articles, ...action.articles.data]
          : action.articles.data,
      };
    case SET_SELECTED_ARTICLE:
      return {
        ...state,
        selectedArticle: action.article,
      };
    case SET_LOADING:
      return {
        ...state,
        isLoading: action.isLoading,
      };
    case SET_PAGINATION_INFO:
      return {
        ...state,
        paginationInfo: action.paginationInfo,
      };
    default:
      return state;
  }
};

export default articleReducer;

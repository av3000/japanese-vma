import { combineReducers } from 'redux';
import application from './application';
import articleReducer from './articles';
import errors from './errors';
import lists from './lists';

const rootReducer = combineReducers({
	errors,
	lists,
	application,
	articles: articleReducer,
});

export default rootReducer;

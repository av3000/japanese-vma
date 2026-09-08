import { combineReducers } from 'redux';
import application from './application';
import articleReducer from './articles';
import errors from './errors';

const rootReducer = combineReducers({
	errors,
	application,
	articles: articleReducer,
});

export default rootReducer;

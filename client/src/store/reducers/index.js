import { combineReducers } from 'redux';
import currentUser from './currentUser';
import errors from './errors';
import articles from './articles';
import lists from './lists';
import application from './application';

const rootReducer = combineReducers({
    currentUser,
    errors,
    articles,
    lists,
    application
});

export default rootReducer;
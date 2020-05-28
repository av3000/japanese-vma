import { combineReducers } from 'redux';
import currentUser from './currentUser';
import errors from './errors';
import articles from './articles';
import application from './application';

const rootReducer = combineReducers({
    currentUser,
    errors,
    articles,
    application
});

export default rootReducer;
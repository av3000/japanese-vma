import { combineReducers } from 'redux';
import currentUser from './currentUser';
import errors from './errors';
import lists from './lists';
import application from './application';

const rootReducer = combineReducers({
    currentUser,
    errors,
    lists,
    application
});

export default rootReducer;
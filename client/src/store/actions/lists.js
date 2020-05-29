import { apiCall } from '../../services/api';
import { addError } from './errors';
import { REMOVE_LIST, LOAD_LISTS } from '../actionTypes';

export const loadLists = lists => ({
    type: LOAD_LISTS,
    lists
});

export const remove = id => ({
    type: REMOVE_LIST,
    id
});

export const fetchLists = () => {
    return dispatch => {
        return apiCall("get", '/api/lists')
            .then(res => {
                dispatch(loadLists(res));
            })
            .catch(err => {
                console.log(err);
                // addError(err.message);
            })
    };
};

export const removeList = (list_id) => {
    return dispatch => {
      return apiCall("delete", `/api/list/${list_id}`)
        .then(res =>  {
            dispatch(remove(list_id)) 
        })
        .catch(err => {
            addError(err.message);
        });
    };
  };
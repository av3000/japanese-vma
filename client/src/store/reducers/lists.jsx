import { LOAD_LISTS, REMOVE_LIST } from '../actionTypes';

const list = (state = [], action) => {
    switch(action.type) {
        case LOAD_LISTS:
            return action.lists.lists;
        case REMOVE_LIST:
            return state.filter(list => list.id !== action.id);
        default:
            return state;
    };
};

export default list;
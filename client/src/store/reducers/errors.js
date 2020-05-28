import  {ADD_ERROR, REMOVE_ERROR } from "../actionTypes";

export default  (state = {message: null}, action ) => {
    switch(action.type) {
        case ADD_ERROR:
            console.log("addError");
            return { ...state, message: action.error };
        case REMOVE_ERROR:
            console.log("removeError");
            return { ...state, message: null };
        default:
            return state;
    }
};
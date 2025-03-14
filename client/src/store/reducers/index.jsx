import { combineReducers } from "redux";
import currentUser from "./currentUser";
import errors from "./errors";
import lists from "./lists";
import application from "./application";
import articleReducer from "./articles";

const rootReducer = combineReducers({
  currentUser,
  errors,
  lists,
  application,
  articles: articleReducer,
});

export default rootReducer;

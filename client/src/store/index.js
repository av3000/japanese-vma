import { createStore, applyMiddleware } from "redux";
import thunk from "redux-thunk";
import { composeWithDevTools } from "redux-devtools-extension"; // Import this helper
import rootReducer from "./reducers"; // Import your root reducer

export function configureStore() {
  const store = createStore(
    rootReducer,
    composeWithDevTools(applyMiddleware(thunk)) // Use composeWithDevTools
  );

  return store;
}

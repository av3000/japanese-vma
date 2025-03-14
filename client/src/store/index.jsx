// src/store/index.jsx
import { configureStore as toolkitConfigureStore } from '@reduxjs/toolkit'; // Use the toolkit's configureStore
import thunk from 'redux-thunk';
import rootReducer from './reducers'; // Adjust the path accordingly

// Change the export function name to 'configureStore'
export const configureStore = () => {
  const store = toolkitConfigureStore({
    reducer: rootReducer,
    middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(thunk),
  });

  return store;
};

// // src/store/index.jsx
// import { configureStore as toolkitConfigureStore } from '@reduxjs/toolkit';
// import rootReducer from './reducers'; // Adjust the path accordingly

// // No need to explicitly import thunk as it's included in Redux Toolkit by default
// export const configureStore = () => {
//   const store = toolkitConfigureStore({
//     reducer: rootReducer,
//     // Redux Toolkit includes thunk by default, no need to add it explicitly
//     middleware: (getDefaultMiddleware) => getDefaultMiddleware(),
//   });
  
//   return store;
// };

import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import errorsReducer from './slices/errorsSlice';
import articlesReducer from './slices/articlesSlice';
import applicationReducer from './slices/applicationSlice';
// Import other reducers here

export const configureAppStore = () => {
  return configureStore({
    reducer: {
      // Map slices to state keys
      currentUser: authReducer, // Keep the same state key for backward compatibility
      errors: errorsReducer,
      articles: articlesReducer,
      application: applicationReducer,
      // Add other reducers here
    }
  });
};

export default configureAppStore;
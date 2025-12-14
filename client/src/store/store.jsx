import { configureStore } from '@reduxjs/toolkit';
import applicationReducer from './slices/applicationSlice';
import articlesReducer from './slices/articlesSlice';
import errorsReducer from './slices/errorsSlice';

// Import other reducers here

export const configureAppStore = () => {
	return configureStore({
		reducer: {
			// Map slices to state keys
			errors: errorsReducer,
			articles: articlesReducer,
			application: applicationReducer,
			// Add other reducers here
		},
	});
};

export default configureAppStore;

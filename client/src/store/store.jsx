import { configureStore } from '@reduxjs/toolkit';
import applicationReducer from './slices/applicationSlice';
import errorsReducer from './slices/errorsSlice';

export const configureAppStore = () => {
	return configureStore({
		reducer: {
			errors: errorsReducer,
			application: applicationReducer,
		},
	});
};

export default configureAppStore;

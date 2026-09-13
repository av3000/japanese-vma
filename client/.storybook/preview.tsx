import { BrowserRouter } from 'react-router-dom';
import type { Preview } from '@storybook/react';

// Storybook doesn't render `src/main.tsx`, so we must explicitly load the app's global styles here.
import '@/assets/font-awesome/css/all.min.css';
import '@/styles/App.css';
import '@/styles/index.css';
import '@/styles/99-utilities/text.css';

const preview: Preview = {
	decorators: [
		(Story) => (
			// BrowserRouter - could be used to reproduce more production'ish behaviour
			<BrowserRouter>
				<Story />
			</BrowserRouter>
		),
	],
	parameters: {
		controls: {
			matchers: {
				color: /(background|color)$/i,
				date: /Date$/i,
			},
		},
	},
};

export default preview;

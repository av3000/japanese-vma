import { BrowserRouter } from 'react-router-dom';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';
import '@fontsource/ibm-plex-mono/600.css';
import '@fontsource/ibm-plex-mono/700.css';
import type { Preview } from '@storybook/react';
// Storybook doesn't render `src/main.tsx`, so we must explicitly load the app's global styles here.
import '@/assets/font-awesome/css/all.min.css';
import '@/styles/App.css';
import '@/styles/index.css';

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

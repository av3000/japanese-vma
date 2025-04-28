import { BrowserRouter } from 'react-router-dom';
import type { Preview } from '@storybook/react';
import '@/styles/index.scss';

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

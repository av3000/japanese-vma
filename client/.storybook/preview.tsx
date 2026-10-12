import type { Preview } from '@storybook/react';
import { authSpies, withAuth, withQueryClient, withRouter, withSocket } from '@/storybook/decorators';
import '@/styles/global-styles';

/** Viewport presets, from the breakpoints the CSS uses (768, 1024, 1320). */
const viewport = (name: string, width: number, type: 'mobile' | 'tablet' | 'desktop') => ({
	name: `${name} (${width}px)`,
	styles: { width: `${width}px`, height: type === 'mobile' ? '780px' : '900px' },
	type,
});

const preview: Preview = {
	// The last decorator is the outermost, so this nests like `App.tsx`: Query > Router > Auth > Socket.
	decorators: [withSocket, withAuth, withRouter, withQueryClient],
	globalTypes: {
		auth: {
			description: 'Who is viewing. A story’s `parameters.auth` wins over this.',
			toolbar: {
				title: 'Viewer',
				icon: 'user',
				items: [
					{ value: 'guest', title: 'Guest' },
					{ value: 'loading', title: 'Checking session' },
					{ value: 'member', title: 'Signed-in member' },
				],
				dynamicTitle: true,
			},
		},
		socket: {
			description: 'Realtime connection state. A story’s `parameters.socket` wins over this.',
			toolbar: {
				title: 'Socket',
				icon: 'transfer',
				items: [
					{ value: 'unconfigured', title: 'Unconfigured' },
					{ value: 'connecting', title: 'Connecting' },
					{ value: 'connected', title: 'Connected' },
					{ value: 'reconnecting', title: 'Reconnecting' },
					{ value: 'failed', title: 'Failed' },
				],
				dynamicTitle: true,
			},
		},
	},
	initialGlobals: { auth: 'guest', socket: 'connected' },
	beforeEach: () => {
		Object.values(authSpies).forEach((spy) => spy.mockClear());
	},
	parameters: {
		options: {
			storySort: {
				order: ['Docs', ['Welcome', 'Authoring'], 'Styleguide', 'Shared', 'UI', 'System', 'Features', 'Pages'],
			},
		},
		controls: {
			matchers: {
				color: /(background|color)$/i,
				date: /Date$/i,
			},
		},
		viewport: {
			viewports: {
				xs: viewport('xs', 360, 'mobile'),
				sm: viewport('sm', 768, 'tablet'),
				md: viewport('md', 1024, 'desktop'),
				lg: viewport('lg', 1320, 'desktop'),
				xl: viewport('xl', 1600, 'desktop'),
			},
		},
	},
};

export default preview;

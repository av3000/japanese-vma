import { faker } from '@faker-js/faker';
import type { Preview } from '@storybook/react-vite';
import { mswLoader } from 'msw-storybook-addon/csf3';
import { configure } from 'storybook/test';
import { authSpies, withAppearance, withAuth, withQueryClient, withRouter, withSocket } from '@/storybook/decorators';
import { startStoryWorker, takeUnhandledRequests } from '@/storybook/msw';
import '@/styles/global-styles';
import { THEME_IDS, themeManifest } from '@/themes/manifest';

// findBy*/waitFor give up after 1 s by default, which is too tight for ~150 story files running in
// parallel browsers on a busy machine or CI runner. 3 s still fails fast on a real regression.
configure({ asyncUtilTimeout: 3000 });

/** Viewport presets, from the breakpoints the CSS uses (768, 1024, 1320). */
const viewport = (name: string, width: number, type: 'mobile' | 'tablet' | 'desktop') => ({
	name: `${name} (${width}px)`,
	styles: { width: `${width}px`, height: type === 'mobile' ? '780px' : '900px' },
	type,
});

const preview: Preview = {
	tags: ['autodocs'],
	// The last decorator is the outermost, so this nests like `App.tsx`: Appearance > Query > Router > Auth > Socket.
	decorators: [withSocket, withAuth, withRouter, withQueryClient, withAppearance],
	globalTypes: {
		theme: {
			description: 'Theme (#498). A story’s `parameters.appearance.theme` wins over this.',
			toolbar: {
				title: 'Theme',
				icon: 'paintbrush',
				items: THEME_IDS.map((id) => ({ value: id, title: themeManifest[id].label })),
				dynamicTitle: true,
			},
		},
		scheme: {
			description:
				'Colour scheme. Ignored by a single-scheme theme; `parameters.appearance.scheme` wins over this.',
			toolbar: {
				title: 'Scheme',
				icon: 'mirror',
				items: [
					{ value: 'light', title: 'Light', icon: 'sun' },
					{ value: 'dark', title: 'Dark', icon: 'moon' },
				],
				dynamicTitle: true,
			},
		},
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
	initialGlobals: { theme: 'kinari', scheme: 'light', auth: 'guest', socket: 'connected' },
	loaders: [mswLoader(startStoryWorker)],
	beforeEach: () => {
		Object.values(authSpies).forEach((spy) => spy.mockClear());
		// Generated handlers draw from faker: one seed per story keeps every run identical.
		faker.seed(173);
		takeUnhandledRequests();
	},
	afterEach: () => {
		const unhandled = takeUnhandledRequests();

		if (unhandled.length > 0) {
			throw new Error(
				`Unmocked API request(s): ${unhandled.join(', ')}. Every endpoint in api.json has a generated ` +
					'handler; anything else needs one (see Docs/Authoring, "Data in stories").',
			);
		}
	},
	parameters: {
		options: {
			storySort: {
				order: [
					'Docs',
					['Welcome', 'Authoring', 'Choosing badges', 'Choosing dialogs'],
					'Styleguide',
					'Shared',
					'UI',
					'System',
					'Features',
					'Pages',
				],
			},
		},
		controls: {
			matchers: {
				color: /(background|color)$/i,
				date: /Date$/i,
			},
		},
		// Violations fail story tests. Disable a rule per story only, with a comment naming it and why.
		a11y: { test: 'error' },
		viewport: {
			options: {
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

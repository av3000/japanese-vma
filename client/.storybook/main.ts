import type { StorybookConfig } from '@storybook/react-vite';
import remarkGfm from 'remark-gfm';
import { storyOptimizeDeps, withoutChecker } from './vite-shared.ts';

const config: StorybookConfig = {
	stories: ['../src/**/*.mdx', '../src/**/*.stories.@(ts|tsx)'],
	addons: [
		// GFM so the Docs pages can use tables.
		{
			name: '@storybook/addon-docs',
			options: { mdxPluginOptions: { mdxCompileOptions: { remarkPlugins: [remarkGfm] } } },
		},
		'@storybook/addon-a11y',
		'@storybook/addon-vitest',
		'msw-storybook-addon',
	],
	// MSW's service worker (#333). Kept here, not in client/public, so it never ships in the app build.
	staticDirs: ['./public'],
	framework: {
		name: '@storybook/react-vite',
		options: {},
	},
	viteFinal: (viteConfig) => ({
		...viteConfig,
		plugins: withoutChecker(viteConfig.plugins),
		optimizeDeps: {
			...viteConfig.optimizeDeps,
			include: [...(viteConfig.optimizeDeps?.include ?? []), ...storyOptimizeDeps],
		},
	}),
};

export default config;

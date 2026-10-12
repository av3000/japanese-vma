import type { StorybookConfig } from '@storybook/react-vite';
import { storyOptimizeDeps, withoutChecker } from './vite-shared';

const config: StorybookConfig = {
	stories: ['../src/**/*.mdx', '../src/**/*.stories.@(ts|tsx)'],
	addons: [
		'@storybook/addon-essentials',
		'@storybook/addon-links',
		'@chromatic-com/storybook',
		'@storybook/experimental-addon-test',
	],
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

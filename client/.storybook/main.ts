import type { StorybookConfig } from '@storybook/react-vite';
import { storyOptimizeDeps, withoutChecker } from './vite-shared.ts';

const config: StorybookConfig = {
	stories: ['../src/**/*.mdx', '../src/**/*.stories.@(ts|tsx)'],
	addons: ['@storybook/addon-docs', '@storybook/addon-a11y', '@storybook/addon-vitest'],
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

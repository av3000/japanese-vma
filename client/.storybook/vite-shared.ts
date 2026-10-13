import type { PluginOption } from 'vite';

/**
 * Dependencies Vite would otherwise discover mid-run. A late discovery re-optimises and reloads,
 * which failed ~33 story tests on a cold cache with "Failed to fetch dynamically imported module".
 */
export const storyOptimizeDeps = [
	'react-dom/client',
	'react-redux',
	'@reduxjs/toolkit',
	'@tanstack/react-query-devtools',
	// Request mocking (#333): the generated handlers pull in faker, which is large. (msw itself is
	// external to Vitest's browser mode and must not be listed.)
	'@faker-js/faker',
];

/** Drops `vite-plugin-checker`: the app's dev server typechecks and lints, Storybook doesn't need to. */
export const withoutChecker = (plugins: PluginOption[] = []): PluginOption[] =>
	plugins
		.map((plugin) => (Array.isArray(plugin) ? withoutChecker(plugin) : plugin))
		.filter(
			(plugin) =>
				!(plugin && typeof plugin === 'object' && 'name' in plugin && plugin.name === 'vite-plugin-checker'),
		);

import { storybookTest } from '@storybook/addon-vitest/vitest-plugin';
import { playwright } from '@vitest/browser-playwright';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';
import { storyOptimizeDeps, withoutChecker } from './.storybook/vite-shared';
import viteConfig from './vite.config';

const dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Two projects on the app's Vite config, without `vite-plugin-checker`:
 * - `unit`: plain Vitest, no browser (`npm test`, `npm run test:unit`);
 * - `storybook`: every story as a test in headless Chromium (`npm run test:storybook`).
 */
export default defineConfig((env) => {
	const app = viteConfig(env);

	return {
		...app,
		plugins: withoutChecker(app.plugins),
		test: {
			projects: [
				{ extends: true, test: { name: 'unit' } },
				{
					extends: true,
					plugins: [storybookTest({ configDir: path.join(dirname, '.storybook') })],
					optimizeDeps: { include: storyOptimizeDeps },
					test: {
						name: 'storybook',
						// A real browser per file, axe on every story: 15 s is tight on a busy machine or CI runner.
						testTimeout: 30000,
						browser: {
							enabled: true,
							headless: true,
							provider: playwright(),
							instances: [{ browser: 'chromium' }],
						},
					},
				},
			],
		},
	};
});

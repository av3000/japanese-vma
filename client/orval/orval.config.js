import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'orval';

const dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(dirname, '..');
const generatedDir = path.resolve(rootDir, 'src/api/generated');

const openApiTarget = process.env.ORVAL_OPENAPI_URL ?? path.resolve(rootDir, '../processor-api/api.json');

export default defineConfig({
	api: {
		input: {
			target: openApiTarget,
		},
		output: {
			mode: 'tags-split',
			target: path.resolve(generatedDir, 'index.ts'),
			schemas: path.resolve(generatedDir, 'model'),
			client: 'react-query',
			httpClient: 'axios',
			clean: true,
			prettier: true,
			urlEncodeParameters: true,
			// MSW handlers and response factories for Storybook (#333). `*/api/v1` matches whatever
			// VITE_API_URL is, but only API paths: a bare `*/articles/:uid` also matched Vite's
			// `/src/.../articles/X.stories.tsx` module requests and answered them with JSON.
			// No artificial delay: stories and tests stay fast.
			mock: { type: 'msw', delay: false, indexMockFiles: true, baseUrl: '*/api/v1' },
			override: {
				mutator: {
					path: path.resolve(rootDir, 'src/services/orval-mutator.ts'),
					name: 'customInstance',
				},
			},
		},
	},
});

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
			mode: 'tags',
			target: path.resolve(generatedDir, 'index.ts'),
			schemas: path.resolve(generatedDir, 'model'),
			client: 'axios-functions',
			httpClient: 'axios',
			clean: true,
			prettier: true,
			indexFiles: false,
			urlEncodeParameters: true,
			override: {
				mutator: {
					path: path.resolve(rootDir, 'src/services/orval-mutator.ts'),
					name: 'customInstance',
				},
			},
		},
	},

	articleQuery: {
		input: {
			target: openApiTarget,
			filters: {
				tags: ['Article'],
			},
		},
		output: {
			mode: 'tags-split',
			target: path.resolve(generatedDir, 'article-query.ts'),
			schemas: path.resolve(generatedDir, 'model'),
			client: 'react-query',
			httpClient: 'axios',
			clean: false,
			prettier: true,
			override: {
				mutator: {
					path: path.resolve(rootDir, 'src/services/orval-mutator.ts'),
					name: 'customInstance',
				},
			},
		},
	},
});

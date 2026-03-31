import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'orval';

const dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(dirname, '..');
const generatedDir = path.resolve(rootDir, 'src/api/generated');

export default defineConfig({
	api: {
		input: {
			target: process.env.ORVAL_OPENAPI_URL ?? path.resolve(rootDir, '../processor-api/api.json'),
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
});

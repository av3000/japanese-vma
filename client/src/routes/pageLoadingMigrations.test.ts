import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const MIGRATED_PENDING_BRANCHES = {
	'./CatalogueDetails/index.tsx': 'detail',
	'./japanese/RadicalsList/index.tsx': 'list',
	'./japanese/RadicalDetails/index.tsx': 'detail',
	'./japanese/KanjisList/index.tsx': 'list',
	'./japanese/KanjiDetails/index.tsx': 'detail',
	'./japanese/WordsList/index.tsx': 'list',
	'./japanese/WordDetails/index.tsx': 'detail',
	'./japanese/SentencesList/index.tsx': 'list',
	'./japanese/SentenceDetails/index.tsx': 'detail',
	'./community/PostsList/index.tsx': 'list',
	'./community/PostDetails/index.tsx': 'detail',
	'./CatalogueEdit/index.tsx': 'form',
	'./community/PostEdit/index.tsx': 'form',
	'./Dashboard/index.tsx': 'dashboard',
} as const;

describe('first-query page loading migrations', () => {
	for (const [relativePath, family] of Object.entries(MIGRATED_PENDING_BRANCHES)) {
		it(`${relativePath} uses the ${family} family`, () => {
			const source = readFileSync(fileURLToPath(new URL(relativePath, import.meta.url)), 'utf8');

			expect(source).toContain(`PageLoading family="${family}"`);
		});
	}
});

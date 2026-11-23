import { describe, expect, it } from 'vitest';
import { readSearchMemory, rememberSearch, SEARCH_MEMORY_KEY } from './recentSearches';

const memoryStorage = (initial: Record<string, string> = {}): Storage => {
	const data = new Map(Object.entries(initial));
	return {
		get length() {
			return data.size;
		},
		clear: () => data.clear(),
		getItem: (key) => data.get(key) ?? null,
		key: (index) => Array.from(data.keys())[index] ?? null,
		removeItem: (key) => void data.delete(key),
		setItem: (key, value) => void data.set(key, value),
	};
};

const throwingStorage = (): Storage => ({
	...memoryStorage(),
	getItem: () => {
		throw new Error('blocked');
	},
	setItem: () => {
		throw new Error('blocked');
	},
});

describe('readSearchMemory', () => {
	it.each([
		['nothing stored', undefined],
		['bad JSON', '{not json'],
		['a non-object', '42'],
	])('falls back to Articles and no recent searches for %s', (_, raw) => {
		const storage = memoryStorage(raw === undefined ? {} : { [SEARCH_MEMORY_KEY]: raw });
		expect(readSearchMemory(storage)).toEqual({ lastScope: 'articles', recent: [] });
	});

	it('drops unknown scopes and empty queries', () => {
		const storage = memoryStorage({
			[SEARCH_MEMORY_KEY]: JSON.stringify({
				lastScope: 'films',
				recent: [
					{ scope: 'kanji', query: '水' },
					{ scope: 'films', query: 'x' },
					{ scope: 'words', query: ' ' },
					7,
				],
			}),
		});

		expect(readSearchMemory(storage)).toEqual({ lastScope: 'articles', recent: [{ scope: 'kanji', query: '水' }] });
	});

	it('survives storage that throws', () => {
		expect(readSearchMemory(throwingStorage())).toEqual({ lastScope: 'articles', recent: [] });
		expect(readSearchMemory(null)).toEqual({ lastScope: 'articles', recent: [] });
	});
});

describe('rememberSearch', () => {
	it('puts the newest search first, trimmed, and remembers its scope', () => {
		const storage = memoryStorage();
		rememberSearch('kanji', ' 水 ', storage);
		const memory = rememberSearch('words', 'たべる', storage);

		expect(memory).toEqual({
			lastScope: 'words',
			recent: [
				{ scope: 'words', query: 'たべる' },
				{ scope: 'kanji', query: '水' },
			],
		});
		expect(readSearchMemory(storage)).toEqual(memory);
	});

	it('keeps one entry per scope and query, moving a repeat to the front', () => {
		const storage = memoryStorage();
		rememberSearch('kanji', '水', storage);
		rememberSearch('words', '水', storage);
		const memory = rememberSearch('kanji', '水', storage);

		expect(memory.recent).toEqual([
			{ scope: 'kanji', query: '水' },
			{ scope: 'words', query: '水' },
		]);
	});

	it('keeps at most five', () => {
		const storage = memoryStorage();
		['a', 'b', 'c', 'd', 'e', 'f'].forEach((query) => rememberSearch('articles', query, storage));

		expect(readSearchMemory(storage).recent.map((entry) => entry.query)).toEqual(['f', 'e', 'd', 'c', 'b']);
	});

	it('records the scope of an empty search without adding a recent entry', () => {
		const storage = memoryStorage();
		rememberSearch('kanji', '水', storage);

		expect(rememberSearch('sentences', '  ', storage)).toEqual({
			lastScope: 'sentences',
			recent: [{ scope: 'kanji', query: '水' }],
		});
	});

	it('still returns the new memory when storage throws', () => {
		expect(rememberSearch('radicals', '木', throwingStorage())).toEqual({
			lastScope: 'radicals',
			recent: [{ scope: 'radicals', query: '木' }],
		});
	});
});

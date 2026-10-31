import { describe, expect, it } from 'vitest';
import { classifyQuery, containsJapanese, pickBestMatch, resolveScope, rowNote, type ScopeCounts } from './detectScope';

describe('classifyQuery', () => {
	it.each([
		['水', 'kanji'],
		['  水  ', 'kanji'],
		['日本', 'japanese'],
		['たべる', 'japanese'],
		['コーヒー', 'japanese'],
		['食べる', 'japanese'],
		['水 water', 'japanese'],
		['water', 'latin'],
		['taberu', 'latin'],
		['123', 'latin'],
		['?!', 'latin'],
		['ｗａｔｅｒ', 'latin'],
		['', 'empty'],
		['   ', 'empty'],
	] as const)('%j is %s', (query, kind) => {
		expect(classifyQuery(query)).toBe(kind);
	});
});

describe('containsJapanese', () => {
	it.each([
		['水', true],
		['たべる', true],
		['grammar', false],
		['', false],
	] as const)('%j -> %s', (text, expected) => {
		expect(containsJapanese(text)).toBe(expected);
	});
});

describe('resolveScope', () => {
	it.each([
		[
			'one kanji is a confident Kanji match',
			{ query: '水', picked: null, lastScope: 'articles' },
			{ scope: 'kanji', bestMatch: 'kanji', order: ['kanji', 'articles', 'words', 'sentences', 'radicals'] },
		],
		[
			'kana is a confident Words match',
			{ query: 'たべる', picked: null, lastScope: 'articles' },
			{ scope: 'words', bestMatch: 'words', order: ['words', 'articles', 'kanji', 'sentences', 'radicals'] },
		],
		[
			'several kanji are a confident Words match',
			{ query: '日本', picked: null, lastScope: 'kanji' },
			{ scope: 'words', bestMatch: 'words', order: ['words', 'articles', 'kanji', 'sentences', 'radicals'] },
		],
		[
			'Latin text goes to Words with no claim, in meaning order',
			{ query: 'water', picked: null, lastScope: 'articles' },
			{ scope: 'words', bestMatch: null, order: ['words', 'kanji', 'radicals', 'sentences', 'articles'] },
		],
		[
			'an empty query uses the last scope',
			{ query: '', picked: null, lastScope: 'sentences' },
			{ scope: 'sentences', bestMatch: null, order: ['sentences', 'articles', 'kanji', 'words', 'radicals'] },
		],
		[
			'a picked chip overrides detection and drops the claim',
			{ query: '水', picked: 'articles', lastScope: 'kanji' },
			{ scope: 'articles', bestMatch: null, order: ['articles', 'kanji', 'words', 'sentences', 'radicals'] },
		],
		[
			'a picked chip overrides the Latin order',
			{ query: 'water', picked: 'sentences', lastScope: 'articles' },
			{ scope: 'sentences', bestMatch: null, order: ['sentences', 'articles', 'kanji', 'words', 'radicals'] },
		],
	] as const)('%s', (_, input, expected) => {
		expect(resolveScope(input)).toMatchObject(expected);
	});
});

const counts = (values: Partial<ScopeCounts>): ScopeCounts => ({
	articles: 0,
	kanji: 0,
	words: 0,
	sentences: 0,
	radicals: 0,
	...values,
});

describe('pickBestMatch', () => {
	const order = ['words', 'kanji', 'radicals', 'sentences', 'articles'] as const;

	it.each([
		['the first scope in order with hits', counts({ words: 1256, kanji: 108, radicals: 1 }), 'words'],
		['skips a leading zero', counts({ kanji: 3, articles: 9 }), 'kanji'],
		['skips a null count', counts({ words: null, sentences: 2 }), 'sentences'],
		['nothing when every scope is zero or null', counts({ articles: null }), null],
	] as const)('%s', (_, values, expected) => {
		expect(pickBestMatch(order, values)).toBe(expected);
	});
});

describe('resolveScope with counts', () => {
	it.each([
		[
			'Latin text claims the first scope with hits, in meaning order',
			{ query: 'water', counts: counts({ words: 1256, kanji: 108, radicals: 1 }) },
			{ scope: 'words', bestMatch: 'words', order: ['words', 'kanji', 'radicals', 'sentences', 'articles'] },
		],
		[
			'a scope with hits moves to the top once Words has none',
			{ query: 'fire', counts: counts({ kanji: 4, articles: 2 }) },
			{ scope: 'kanji', bestMatch: 'kanji', order: ['kanji', 'words', 'radicals', 'sentences', 'articles'] },
		],
		[
			'a single kanji with no kanji hits hands the claim to the next scope',
			{ query: '水', counts: counts({ words: 312, sentences: 87, articles: null }) },
			{ scope: 'words', bestMatch: 'words', order: ['words', 'kanji', 'articles', 'sentences', 'radicals'] },
		],
		[
			'a single kanji keeps Kanji when it has hits, even with fewer than Words',
			{ query: '水', counts: counts({ kanji: 1, words: 312 }) },
			{ scope: 'kanji', bestMatch: 'kanji', order: ['kanji', 'articles', 'words', 'sentences', 'radicals'] },
		],
		[
			'no claim and detection order when nothing has hits',
			{ query: 'たべる', counts: counts({}) },
			{ scope: 'words', bestMatch: null, order: ['words', 'articles', 'kanji', 'sentences', 'radicals'] },
		],
		[
			'a picked chip still wins over counts',
			{ query: 'water', picked: 'articles', counts: counts({ words: 5 }) },
			{ scope: 'articles', bestMatch: null, order: ['articles', 'kanji', 'words', 'sentences', 'radicals'] },
		],
	] as const)('%s', (_, input, expected) => {
		expect(resolveScope({ picked: null, lastScope: 'articles', ...input })).toMatchObject(expected);
	});

	it('never makes a zero-count scope the best match', () => {
		const resolution = resolveScope({
			query: '水',
			picked: null,
			lastScope: 'articles',
			counts: counts({ kanji: 0, words: 0, sentences: 3 }),
		});

		expect(resolution.bestMatch).toBe('sentences');
	});
});

describe('rowNote', () => {
	const notes = (query: string, picked: 'articles' | null = null) => {
		const resolution = resolveScope({ query, picked, lastScope: 'articles' });
		return resolution.order.map((row, index) => rowNote(row, index, resolution));
	};

	it('marks the kanji row for a single kanji, even after another chip is picked', () => {
		expect(notes('水')).toEqual(['single character detected', null, null, null, null]);
		expect(notes('水', 'articles')).toEqual([null, 'single character detected', null, null, null]);
	});

	it('marks the words row for Japanese text', () => {
		expect(notes('たべる')).toEqual(['Japanese text detected', null, null, null, null]);
	});

	it('says the first row searches English meanings for unpicked Latin text only', () => {
		expect(notes('water')).toEqual(['searches English meanings', null, null, null, null]);
		expect(notes('water', 'articles')).toEqual([null, null, null, null, null]);
	});

	it.each([
		['kanji', counts({ kanji: 4 }), 'searches English meanings'],
		['articles', counts({ articles: 2 }), null],
		['sentences', counts({ sentences: 1 }), null],
	] as const)(
		'keeps the English-meanings note off a first row it does not describe (%s first)',
		(first, hits, note) => {
			const resolution = resolveScope({ query: 'Tokyo', picked: null, lastScope: 'articles', counts: hits });

			expect(resolution.order[0]).toBe(first);
			expect(rowNote(resolution.order[0], 0, resolution)).toBe(note);
		},
	);
});

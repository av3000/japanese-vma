import { describe, expect, it } from 'vitest';
import { classifyQuery, containsJapanese, resolveScope, rowNote } from './detectScope';

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
});

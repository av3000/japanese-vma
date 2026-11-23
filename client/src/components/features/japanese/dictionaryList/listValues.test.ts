import { describe, expect, it } from 'vitest';
import { presentAllValues, presentRank, presentText, presentValues, toJlptLevel } from './listValues';

describe('toJlptLevel', () => {
	it.each([
		['1', 'N1'],
		['5', 'N5'],
		[3, 'N3'],
		['N2', 'N2'],
		['n4', 'N4'],
		[' N5 ', 'N5'],
	])('reads %j as %s', (value, expected) => {
		expect(toJlptLevel(value)).toBe(expected);
	});

	it.each([null, undefined, '', '-', '0', '6', 'N6', 'common', 'uncommon', 'N1-N2'])(
		'treats %j as no level',
		(value) => {
			expect(toJlptLevel(value)).toBeNull();
		},
	);
});

describe('presentText', () => {
	it('returns trimmed text', () => {
		expect(presentText('  かける ')).toBe('かける');
	});

	it.each([null, undefined, '', '  ', '-', ' - '])('treats %j as missing', (value) => {
		expect(presentText(value)).toBeNull();
	});
});

describe('presentValues', () => {
	it('keeps the first three real entries', () => {
		expect(presentValues(['ふさ.ぐ', 'しげ.る', 'しげ.み', 'さか.ん'])).toEqual(['ふさ.ぐ', 'しげ.る', 'しげ.み']);
	});

	it('drops the "-" placeholder the kanji API sends for an empty field', () => {
		expect(presentValues(['-'])).toEqual([]);
		expect(presentValues(['', '-', 'ウツ'])).toEqual(['ウツ']);
	});

	it('accepts a missing array', () => {
		expect(presentValues(null)).toEqual([]);
		expect(presentValues(undefined)).toEqual([]);
	});

	it('keeps every real entry through presentAllValues', () => {
		const types = ['noun', '-', 'adverb', 'suru verb', 'no-adjective'];

		expect(presentAllValues(types)).toEqual(['noun', 'adverb', 'suru verb', 'no-adjective']);
		expect(presentAllValues(undefined)).toEqual([]);
	});

	it('takes a custom limit', () => {
		expect(presentValues(['a', 'b', 'c', 'd'], 2)).toEqual(['a', 'b']);
	});
});

describe('presentRank', () => {
	it('keeps positive numbers', () => {
		expect(presentRank(1)).toBe(1);
		expect(presentRank(2071)).toBe(2071);
	});

	it('treats 0 and null as no rank', () => {
		expect(presentRank(0)).toBeNull();
		expect(presentRank(null)).toBeNull();
		expect(presentRank(undefined)).toBeNull();
	});
});

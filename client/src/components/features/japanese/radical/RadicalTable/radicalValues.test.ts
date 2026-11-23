import { describe, expect, it } from 'vitest';
import { splitRadicalGlyph, splitRadicalReading } from './radicalValues';

describe('splitRadicalGlyph', () => {
	it('keeps a plain glyph', () => {
		expect(splitRadicalGlyph('一')).toEqual({ glyph: '一', variants: [] });
	});

	it('separates the variant forms in brackets', () => {
		expect(splitRadicalGlyph('乙 (乛、⺄、乚、乙、乀)')).toEqual({
			glyph: '乙',
			variants: ['乛', '⺄', '乚', '乙', '乀'],
		});
		expect(splitRadicalGlyph('人 (亻)')).toEqual({ glyph: '人', variants: ['亻'] });
		expect(splitRadicalGlyph('八（丷）')).toEqual({ glyph: '八', variants: ['丷'] });
	});

	it('treats a missing glyph as none', () => {
		expect(splitRadicalGlyph(null)).toEqual({ glyph: null, variants: [] });
		expect(splitRadicalGlyph('-')).toEqual({ glyph: null, variants: [] });
	});
});

describe('splitRadicalReading', () => {
	it('splits kana from romaji around the slash and the no-break space', () => {
		expect(splitRadicalReading('いち\u00a0/ ichi')).toEqual({ kana: 'いち', romaji: 'ichi' });
		expect(splitRadicalReading('つつみがまえ / tsutsumigamae')).toEqual({
			kana: 'つつみがまえ',
			romaji: 'tsutsumigamae',
		});
	});

	it('keeps a kana-only reading', () => {
		expect(splitRadicalReading('さんずい')).toEqual({ kana: 'さんずい', romaji: null });
	});

	it('treats a missing reading as none', () => {
		expect(splitRadicalReading(null)).toEqual({ kana: null, romaji: null });
		expect(splitRadicalReading('')).toEqual({ kana: null, romaji: null });
	});
});

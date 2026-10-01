import { describe, expect, it } from 'vitest';
import { LONGEST_JAPANESE_TITLE } from '@/components/features/Homepage/fixtures';
import {
	articleCoverGlyph,
	articleCoverLevel,
	catalogueCoverGlyph,
	catalogueJlptCounts,
	FALLBACK_COVER_GLYPH,
} from './coverRule';

const noCounts = { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 };

describe('articleCoverGlyph', () => {
	it('takes the first kanji of the Japanese title', () => {
		expect(articleCoverGlyph('毎年交流してきたお年よりだけど')).toBe('毎');
		expect(articleCoverGlyph(LONGEST_JAPANESE_TITLE)).toBe('東');
	});

	it('skips kana, Latin and digits before the first kanji', () => {
		expect(articleCoverGlyph('いっしょに歩こう')).toBe('歩');
		expect(articleCoverGlyph('NHK 2026年のニュース')).toBe('年');
	});

	it('skips the iteration mark, which means nothing alone', () => {
		expect(articleCoverGlyph('々木')).toBe('木');
	});

	it('keeps a kanji outside the Basic Multilingual Plane whole', () => {
		expect(articleCoverGlyph('の𠮟る')).toBe('𠮟');
	});

	it('falls back to 記 for a title without kanji', () => {
		expect(articleCoverGlyph('いっしょにあそぼう')).toBe(FALLBACK_COVER_GLYPH);
		expect(articleCoverGlyph('Hello')).toBe(FALLBACK_COVER_GLYPH);
		expect(articleCoverGlyph('')).toBe(FALLBACK_COVER_GLYPH);
	});
});

describe('articleCoverLevel', () => {
	it('is the dominant JLPT level', () => {
		expect(articleCoverLevel({ ...noCounts, n4: 33, n5: 27, n3: 30 })).toBe('N4');
	});

	it('is null when no level has kanji, even with uncommon ones', () => {
		expect(articleCoverLevel(noCounts)).toBeNull();
		expect(articleCoverLevel({ ...noCounts, uncommon: 4 })).toBeNull();
	});
});

describe('catalogueCoverGlyph', () => {
	it.each([
		[5, '部'],
		[1, '部'],
		[6, '字'],
		[2, '字'],
		[7, '語'],
		[3, '語'],
		[8, '文'],
		[4, '文'],
		[9, '記'],
	])('gives type %i the glyph %s', (type, glyph) => {
		expect(catalogueCoverGlyph(type)).toBe(glyph);
	});

	it('falls back to 記 for lyrics, artists and unknown types', () => {
		expect(catalogueCoverGlyph(10)).toBe(FALLBACK_COVER_GLYPH);
		expect(catalogueCoverGlyph(11)).toBe(FALLBACK_COVER_GLYPH);
		expect(catalogueCoverGlyph(99)).toBe(FALLBACK_COVER_GLYPH);
	});
});

describe('catalogueJlptCounts', () => {
	it('counts words for Words and Sentences catalogues', () => {
		expect([7, 3, 8, 4].map(catalogueJlptCounts)).toEqual(['words', 'words', 'words', 'words']);
	});

	it('counts kanji for Kanji and Articles catalogues', () => {
		expect([6, 2, 9].map(catalogueJlptCounts)).toEqual(['kanji', 'kanji', 'kanji']);
	});
});

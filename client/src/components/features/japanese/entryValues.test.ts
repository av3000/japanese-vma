import { describe, expect, it } from 'vitest';
import {
	frequencyLabel,
	inTextOrder,
	kanjiGradeLabel,
	kanjiParts,
	kanjiRadicals,
	kunReadingParts,
	radicalGlyphParts,
	radicalReadingParts,
	sentenceSource,
} from './entryValues';
import { KANGXI_RADICALS, kangxiRadicalGlyph } from './kangxiRadicals';

describe('kunReadingParts', () => {
	it('splits the okurigana off at the dot', () => {
		expect(kunReadingParts('さか.える')).toEqual({ prefix: '', stem: 'さか', okurigana: 'える', suffix: '' });
	});

	it('keeps suffix and prefix marks apart from the reading', () => {
		expect(kunReadingParts('-ば.え')).toEqual({ prefix: '-', stem: 'ば', okurigana: 'え', suffix: '' });
		expect(kunReadingParts('こ-')).toEqual({ prefix: '', stem: 'こ', okurigana: '', suffix: '-' });
	});

	it('returns a reading without okurigana whole', () => {
		expect(kunReadingParts('ひと')).toEqual({ prefix: '', stem: 'ひと', okurigana: '', suffix: '' });
	});

	it('treats the missing markers as no reading', () => {
		expect(kunReadingParts('-')).toBeNull();
		expect(kunReadingParts('')).toBeNull();
		expect(kunReadingParts(null)).toBeNull();
	});
});

describe('kanjiGradeLabel', () => {
	it.each([
		['1', 'Grade 1'],
		['6', 'Grade 6'],
		['8', 'Secondary school'],
		['9', 'Name kanji'],
		['10', 'Name kanji'],
	])('labels grade %s as %s', (grade, label) => {
		expect(kanjiGradeLabel(grade)).toBe(label);
	});

	it.each([null, '', '-', '7', '11'])('has no label for %s', (grade) => {
		expect(kanjiGradeLabel(grade)).toBeNull();
	});
});

describe('frequencyLabel', () => {
	it('formats a rank and drops the 0 that means none', () => {
		expect(frequencyLabel(648)).toBe('#648');
		expect(frequencyLabel(2301)).toBe('#2,301');
		expect(frequencyLabel(0)).toBeNull();
		expect(frequencyLabel(null)).toBeNull();
	});
});

describe('kanjiRadicals', () => {
	it('turns Kangxi numbers into glyphs with their pages', () => {
		expect(kanjiRadicals(['75'])).toEqual([{ number: 75, glyph: '木', href: '/radical/75' }]);
		expect(kanjiRadicals(['74', '130']).map((radical) => radical.glyph)).toEqual(['月', '肉']);
	});

	it('reads a comma-joined value and skips anything that is not a radical number', () => {
		expect(kanjiRadicals(['74,130,']).map((radical) => radical.number)).toEqual([74, 130]);
		expect(kanjiRadicals(['-', '0', '215', 'power'])).toEqual([]);
		expect(kanjiRadicals(undefined)).toEqual([]);
	});
});

describe('kangxiRadicals', () => {
	it('holds all 214 radicals in order', () => {
		expect(KANGXI_RADICALS).toHaveLength(214);
		expect(kangxiRadicalGlyph(1)).toBe('一');
		expect(kangxiRadicalGlyph(85)).toBe('水');
		expect(kangxiRadicalGlyph(214)).toBe('龠');
		expect(kangxiRadicalGlyph(215)).toBeNull();
	});
});

describe('kanjiParts', () => {
	it('splits the space-separated parts', () => {
		expect(kanjiParts(['十 辛 木 立'])).toEqual(['十', '辛', '木', '立']);
		expect(kanjiParts([' 皮 又 彳'])).toEqual(['皮', '又', '彳']);
		expect(kanjiParts(['-'])).toEqual([]);
	});
});

describe('radicalGlyphParts', () => {
	it('separates the glyph from the variants line', () => {
		expect(radicalGlyphParts('水\n(氵、氺)')).toEqual({ glyph: '水', variants: ['氵', '氺'] });
	});

	it('leaves out a variant that repeats the glyph', () => {
		expect(radicalGlyphParts('乙\n(乛、⺄、乚、乙、乀)').variants).toEqual(['乛', '⺄', '乚', '乀']);
	});

	it('returns a plain glyph with no variants', () => {
		expect(radicalGlyphParts('木')).toEqual({ glyph: '木', variants: [] });
		expect(radicalGlyphParts(null)).toEqual({ glyph: null, variants: [] });
	});
});

describe('radicalReadingParts', () => {
	it('splits kana from romaji, including a non-breaking space', () => {
		expect(radicalReadingParts('き / ki')).toEqual({ kana: 'き', romaji: 'ki' });
		expect(radicalReadingParts('みず / mizu')).toEqual({ kana: 'みず', romaji: 'mizu' });
	});

	it('keeps kana with no romaji, and nothing for a missing name', () => {
		expect(radicalReadingParts('ア')).toEqual({ kana: 'ア', romaji: null });
		expect(radicalReadingParts('-')).toBeNull();
		expect(radicalReadingParts(null)).toBeNull();
	});
});

describe('sentenceSource', () => {
	it('links a Tatoeba import to its entry', () => {
		expect(sentenceSource({ user_id: null, tatoeba_entry: '347897' }, undefined)).toEqual({
			kind: 'tatoeba',
			entry: '347897',
			href: 'https://tatoeba.org/eng/sentences/show/347897',
		});
	});

	it('credits a member sentence to its owner or to a member, by the author name', () => {
		const author = { name: 'Kenji' };

		expect(sentenceSource({ user_id: 7, tatoeba_entry: null, author }, 7)).toEqual({ kind: 'own', name: 'Kenji' });
		expect(sentenceSource({ user_id: 7, tatoeba_entry: null, author }, 8)).toEqual({
			kind: 'member',
			name: 'Kenji',
		});
		expect(sentenceSource({ user_id: 7, tatoeba_entry: null, author }, undefined)).toEqual({
			kind: 'member',
			name: 'Kenji',
		});
	});

	it('leaves the name out when the payload has no author', () => {
		expect(sentenceSource({ user_id: 7, tatoeba_entry: null, author: null }, 8)).toEqual({
			kind: 'member',
			name: null,
		});
		expect(sentenceSource({ user_id: 7, tatoeba_entry: null }, 7)).toEqual({ kind: 'own', name: null });
	});

	it('has no source for an import without an entry', () => {
		expect(sentenceSource({ user_id: null, tatoeba_entry: null }, 7)).toBeNull();
	});
});

describe('inTextOrder', () => {
	it('orders kanji by their first appearance, keeping missing ones last', () => {
		const kanji = ['事', '公', '共', '業', '他'].map((character) => ({ character }));

		expect(inTextOrder(kanji, '公共事業').map((item) => item.character)).toEqual(['公', '共', '事', '業', '他']);
	});
});

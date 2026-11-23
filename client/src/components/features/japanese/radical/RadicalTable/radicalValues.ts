import { presentText } from '@/components/features/japanese/dictionaryList/listValues';

/*
 * Radical payloads pack two things into one string each:
 * - `radical`: the glyph, then its variant forms in brackets, e.g. "乙 (乛、⺄、乚、乙、乀)" (50 of 214);
 * - `hiragana`: the reading in kana, then romaji, e.g. "いち / ichi" (all 214, with a no-break space).
 * Splitting them lets the glyph column stay one glyph wide and keeps romaji out of the Japanese font.
 */

export interface RadicalGlyph {
	glyph: string | null;
	variants: string[];
}

export const splitRadicalGlyph = (value: string | null | undefined): RadicalGlyph => {
	const text = presentText(value);

	if (!text) return { glyph: null, variants: [] };

	const match = text.match(/^(.+?)\s*[(（](.+)[)）]$/u);

	if (!match) return { glyph: text, variants: [] };

	return {
		glyph: match[1].trim(),
		variants: match[2]
			.split(/[、,]/u)
			.map((variant) => variant.trim())
			.filter(Boolean),
	};
};

export interface RadicalReading {
	kana: string | null;
	romaji: string | null;
}

export const splitRadicalReading = (value: string | null | undefined): RadicalReading => {
	const text = presentText(value);

	if (!text) return { kana: null, romaji: null };

	const [kana, ...rest] = text.split('/');

	return { kana: presentText(kana), romaji: presentText(rest.join('/')) };
};

/**
 * Kana script folding for answer grading. Hiragana U+3041–U+3096 and katakana U+30A1–U+30F6
 * are the same syllables 0x60 apart; everything else (ー, punctuation, latin, kanji) passes
 * through untouched. Phase 2 may replace this with wanakana when romaji input arrives.
 */

const HIRAGANA_START = 0x3041;
const HIRAGANA_END = 0x3096;
const KATAKANA_START = 0x30a1;
const KATAKANA_END = 0x30f6;
const SCRIPT_OFFSET = KATAKANA_START - HIRAGANA_START;

const mapCodePoints = (value: string, map: (codePoint: number) => number): string =>
	Array.from(value, (char) => String.fromCodePoint(map(char.codePointAt(0) ?? 0))).join('');

export const toHiragana = (value: string): string =>
	mapCodePoints(value, (cp) => (cp >= KATAKANA_START && cp <= KATAKANA_END ? cp - SCRIPT_OFFSET : cp));

export const toKatakana = (value: string): string =>
	mapCodePoints(value, (cp) => (cp >= HIRAGANA_START && cp <= HIRAGANA_END ? cp + SCRIPT_OFFSET : cp));

export const isKana = (value: string): boolean =>
	value.length > 0 &&
	Array.from(value).every((char) => {
		const cp = char.codePointAt(0) ?? 0;
		return (
			(cp >= HIRAGANA_START && cp <= HIRAGANA_END) ||
			(cp >= KATAKANA_START && cp <= KATAKANA_END) ||
			cp === 0x30fc // ー prolonged sound mark
		);
	});

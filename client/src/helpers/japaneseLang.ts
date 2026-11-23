const FIRST_LETTER = /\p{L}/u;
const JAPANESE = /[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}]/u;

/**
 * `ja` when the text starts in Japanese (its first letter is a kanji or kana), so Japanese titles
 * get Japanese line breaking and fonts. An English title that quotes は or が stays English:
 * marking it `ja` would set its Latin text in the Japanese font.
 */
export const japaneseLang = (text: string | null | undefined): 'ja' | undefined => {
	const first = (text ?? '').match(FIRST_LETTER)?.[0];

	return first && JAPANESE.test(first) ? 'ja' : undefined;
};

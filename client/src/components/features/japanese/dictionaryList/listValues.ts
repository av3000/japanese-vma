import type { JlptLevel } from '@/components/shared/LevelBadge';

/*
 * The dictionary list payloads report a missing value in several ways: `null`, an empty string,
 * a literal `"-"` (`furigana`, word `jlpt`), `["-"]` (kanji readings and meanings) and `0` (kanji
 * `frequency`). These helpers turn all of them into "nothing to show", so cells render one dash.
 */

const MISSING_TEXT = new Set(['', '-']);

/** The value, trimmed, or `null` when the API means "none". */
export const presentText = (value: string | null | undefined): string | null => {
	const trimmed = value?.trim() ?? '';

	return MISSING_TEXT.has(trimmed) ? null : trimmed;
};

/** The real entries of a multi-valued field, at most `limit` of them. */
export const presentValues = (values: readonly string[] | null | undefined, limit = 3): string[] =>
	(values ?? [])
		.map(presentText)
		.filter((value): value is string => value !== null)
		.slice(0, limit);

/** A positive count or rank, or `null`: the kanji API sends `0` for "no frequency rank". */
export const presentRank = (value: number | null | undefined): number | null =>
	typeof value === 'number' && value > 0 ? value : null;

/**
 * One JLPT reading for both lists: kanji send `"1"`…`"5"` or `null`, words send `"N5"`-style
 * strings or `"-"`. Anything that is not a level 1–5 is "no level".
 */
export const toJlptLevel = (value: string | number | null | undefined): JlptLevel | null => {
	const match = String(value ?? '')
		.trim()
		.match(/^n?([1-5])$/i);

	return match ? (`N${match[1]}` as JlptLevel) : null;
};

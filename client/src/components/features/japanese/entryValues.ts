import { presentAllValues, presentRank, presentText } from './dictionaryList/listValues';
import { kangxiRadicalGlyph } from './kangxiRadicals';

/*
 * Values for the dictionary detail pages (#352), read out of the payloads' storage formats so the
 * pages render parts and labels, never string surgery in JSX. Missing values follow the same rules
 * as the tables (`listValues.ts`): `-`, empty and `null` are "none".
 */

export interface KunReadingParts {
	/** `-` when the reading is a suffix, e.g. `-ば.え`. */
	prefix: string;
	stem: string;
	/** The kana written after the kanji, after the `.` in the source, e.g. `える` in `さか.える`. */
	okurigana: string;
	/** `-` when the reading is a prefix, e.g. `こ-`. */
	suffix: string;
}

/**
 * Splits a kun'yomi as the dictionary writes it: `.` separates the stem from its okurigana, a
 * leading or trailing `-` marks a suffix or prefix reading.
 */
export const kunReadingParts = (value: string | null | undefined): KunReadingParts | null => {
	const reading = presentText(value);

	if (!reading) return null;

	const prefix = reading.startsWith('-') ? '-' : '';
	const suffix = reading.length > 1 && reading.endsWith('-') ? '-' : '';
	const core = reading.slice(prefix.length, reading.length - suffix.length);
	const dot = core.indexOf('.');

	return {
		prefix,
		stem: dot === -1 ? core : core.slice(0, dot),
		okurigana: dot === -1 ? '' : core.slice(dot + 1),
		suffix,
	};
};

/**
 * The school grade a kanji is taught in, as the dictionary numbers it: 1–6 are the elementary
 * grades, 8 the rest of the jōyō list taught in secondary school, 9 and 10 the jinmeiyō name kanji.
 */
export const kanjiGradeLabel = (grade: string | null | undefined): string | null => {
	const value = presentText(grade);

	if (value === null) return null;
	if (/^[1-6]$/.test(value)) return `Grade ${value}`;
	if (value === '8') return 'Secondary school';
	if (value === '9' || value === '10') return 'Name kanji';

	return null;
};

const rankFormat = new Intl.NumberFormat('en-US');

/** A frequency rank as "#648", or `null` for no rank (the API sends `0`). */
export const frequencyLabel = (frequency: number | null | undefined): string | null => {
	const rank = presentRank(frequency);

	return rank === null ? null : `#${rankFormat.format(rank)}`;
};

export interface KanjiRadical {
	number: number;
	glyph: string;
	href: string;
}

/** A kanji's radicals, sent as Kangxi numbers (`["74", "130"]`), as glyphs with their pages. */
export const kanjiRadicals = (radicals: readonly string[] | null | undefined): KanjiRadical[] =>
	presentAllValues(radicals)
		.flatMap((value) => value.split(','))
		.map((value) => Number(value.trim()))
		.flatMap((number) => {
			const glyph = kangxiRadicalGlyph(number);

			return glyph ? [{ number, glyph, href: `/radical/${number}` }] : [];
		});

/** A kanji's component parts, sent as one space-separated string (`["十 辛 木 立"]`). */
export const kanjiParts = (parts: readonly string[] | null | undefined): string[] =>
	presentAllValues(parts).flatMap((value) => value.split(/\s+/u).filter(Boolean));

export interface RadicalGlyphParts {
	glyph: string | null;
	/** Other forms the radical takes inside a kanji, e.g. 氵 and 氺 for 水. */
	variants: string[];
}

/** A radical as stored: the glyph, then its variants on a second line in brackets (`水\n(氵、氺)`). */
export const radicalGlyphParts = (radical: string | null | undefined): RadicalGlyphParts => {
	const value = presentText(radical);

	if (value === null) return { glyph: null, variants: [] };

	const [first, ...rest] = value.split(/\r?\n/u);
	const glyph = first.trim() || null;
	const variants = rest
		.join(' ')
		.replace(/[()（）]/gu, ' ')
		.split(/[、,\s]+/u)
		.filter((variant) => variant !== '' && variant !== glyph);

	return { glyph, variants: Array.from(new Set(variants)) };
};

export interface RadicalReadingParts {
	kana: string;
	romaji: string | null;
}

/** A radical's name as stored: kana, a slash, then romaji (`き / ki`). */
export const radicalReadingParts = (hiragana: string | null | undefined): RadicalReadingParts | null => {
	const value = presentText(hiragana);

	if (value === null) return null;

	const [kana, romaji] = value.split('/').map((part) => part.trim());

	return kana ? { kana, romaji: romaji || null } : null;
};

export type SentenceSource = { kind: 'tatoeba'; entry: string; href: string } | { kind: 'own' } | { kind: 'member' };

/**
 * Where a sentence comes from: an import from Tatoeba (no author), or a member's own sentence.
 * The payload has no author name yet (#549), so another member's sentence is credited generically.
 */
export const sentenceSource = (
	sentence: { user_id: number | null; tatoeba_entry: string | null },
	viewerId: number | null | undefined,
): SentenceSource | null => {
	if (sentence.user_id !== null) {
		return viewerId != null && sentence.user_id === viewerId ? { kind: 'own' } : { kind: 'member' };
	}

	const entry = presentText(sentence.tatoeba_entry);

	return entry
		? { kind: 'tatoeba', entry, href: `https://tatoeba.org/eng/sentences/show/${encodeURIComponent(entry)}` }
		: null;
};

/**
 * Kanji in the order they first appear in a word or sentence, rather than the dictionary order the
 * payload sends. Kanji that do not appear keep their order at the end.
 */
export const inTextOrder = <Item extends { character: string }>(items: readonly Item[], text: string): Item[] => {
	const position = (item: Item) => {
		const index = text.indexOf(item.character);

		return index === -1 ? Number.MAX_SAFE_INTEGER : index;
	};

	return items
		.map((item, order) => ({ item, order }))
		.sort((a, b) => position(a.item) - position(b.item) || a.order - b.order)
		.map(({ item }) => item);
};

import { SEARCH_SCOPES, type SearchScope } from './searchScopes';

const HAN = /\p{Script=Han}/u;
const KANA = /[\p{Script=Hiragana}\p{Script=Katakana}]/u;

/**
 * What the characters of a query say about it:
 * - `kanji`: exactly one kanji
 * - `japanese`: any other text with kana or kanji in it
 * - `latin`: text with neither (English, romaji, digits)
 * - `empty`: nothing but whitespace
 */
export type QueryKind = 'empty' | 'kanji' | 'japanese' | 'latin';

export const classifyQuery = (query: string): QueryKind => {
	const trimmed = query.trim();
	if (trimmed === '') return 'empty';
	if ([...trimmed].length === 1 && HAN.test(trimmed)) return 'kanji';
	if (HAN.test(trimmed) || KANA.test(trimmed)) return 'japanese';
	return 'latin';
};

/** True when the text should render in the Japanese font stack with `lang="ja"`. */
export const containsJapanese = (text: string): boolean => HAN.test(text) || KANA.test(text);

/** Latin text matches English meanings on kanji and words, and titles on articles. */
const LATIN_ORDER: readonly SearchScope[] = ['words', 'kanji', 'radicals', 'sentences', 'articles'];

const DETECTED_SCOPE: Record<QueryKind, SearchScope | null> = {
	empty: null,
	kanji: 'kanji',
	japanese: 'words',
	latin: 'words',
};

/**
 * Hits per scope for the current query, as the list would report them. `null` means that list
 * rejects the query (for example a one-character article search), so there is no number to show.
 */
export type ScopeCounts = Record<SearchScope, number | null>;

export interface ScopeResolution {
	kind: QueryKind;
	/** The scope Enter and the primary button search. */
	scope: SearchScope;
	/**
	 * The scope whose chip says "Best match", only when nothing was picked. With counts it is the
	 * first scope in detection order that has results; without them, detection's confident guess.
	 */
	bestMatch: SearchScope | null;
	/** Chip and row order. */
	order: SearchScope[];
	/** Whether a picked chip, not detection, chose `scope`. */
	isPicked: boolean;
}

export interface ResolveScopeInput {
	query: string;
	/** A chip the reader picked; it overrides detection until the query is cleared. */
	picked: SearchScope | null;
	/** Used when the query is empty and nothing is picked. */
	lastScope: SearchScope;
	/** Hits for this query, once known. Without them detection decides alone, as in phase 1. */
	counts?: ScopeCounts | null;
}

const leadWith = (first: SearchScope, rest: readonly SearchScope[]): SearchScope[] => [
	first,
	...rest.filter((value) => value !== first),
];

/** The first scope in `order` with at least one hit, or `null` when none has any. */
export const pickBestMatch = (order: readonly SearchScope[], counts: ScopeCounts): SearchScope | null =>
	order.find((scope) => (counts[scope] ?? 0) > 0) ?? null;

/**
 * Detection orders the scopes by what the characters suggest: a single kanji leads with Kanji,
 * Japanese text with Words, and Latin text with Words, Kanji, Radicals, Sentences, Articles (English
 * meanings match on the dictionary). Once counts arrive they decide: the first scope in that order
 * with hits becomes the best match and moves to the top; a scope with none is never claimed. A
 * picked chip overrides both until the query is cleared.
 */
export const resolveScope = ({ query, picked, lastScope, counts }: ResolveScopeInput): ScopeResolution => {
	const kind = classifyQuery(query);
	const detected = DETECTED_SCOPE[kind];
	const allScopes = SEARCH_SCOPES.map((candidate) => candidate.value);

	if (picked !== null || detected === null) {
		const scope = picked ?? lastScope;
		return { kind, scope, bestMatch: null, order: leadWith(scope, allScopes), isPicked: picked !== null };
	}

	const detectedOrder = kind === 'latin' ? [...LATIN_ORDER] : leadWith(detected, allScopes);

	if (counts) {
		const best = pickBestMatch(detectedOrder, counts);
		return best
			? { kind, scope: best, bestMatch: best, order: leadWith(best, detectedOrder), isPicked: false }
			: { kind, scope: detected, bestMatch: null, order: detectedOrder, isPicked: false };
	}

	// Latin text matches English meanings on both Words and Kanji, so detection alone makes no claim.
	const bestMatch = kind === 'latin' ? null : detected;
	return { kind, scope: detected, bestMatch, order: detectedOrder, isPicked: false };
};

/**
 * The short note after a row's text, saying why detection put that scope where it is. The note for
 * a detected kanji or Japanese text stays on its row even when the reader picks another scope.
 */
export const rowNote = (row: SearchScope, index: number, resolution: ScopeResolution): string | null => {
	if (resolution.kind === 'kanji' && row === 'kanji') return 'single character detected';
	if (resolution.kind === 'japanese' && row === 'words') return 'Japanese text detected';
	if (resolution.kind === 'latin' && !resolution.isPicked && index === 0) return 'searches English meanings';
	return null;
};

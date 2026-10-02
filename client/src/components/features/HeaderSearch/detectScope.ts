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

export interface ScopeResolution {
	kind: QueryKind;
	/** The scope Enter and the primary button search. */
	scope: SearchScope;
	/** The scope whose chip says "Best match"; only when detection is confident and nothing was picked. */
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
}

/**
 * Phase 1 of the Header search guesses the scope from character classes alone. Latin text goes to
 * Words without a "Best match" claim, because English meanings match on both Words and Kanji. Real
 * per-scope counts replace this guess in #383.
 */
export const resolveScope = ({ query, picked, lastScope }: ResolveScopeInput): ScopeResolution => {
	const kind = classifyQuery(query);
	const detected = DETECTED_SCOPE[kind];
	const scope = picked ?? detected ?? lastScope;
	const bestMatch = picked === null && (kind === 'kanji' || kind === 'japanese') ? detected : null;

	const order =
		kind === 'latin' && picked === null
			? [...LATIN_ORDER]
			: [scope, ...SEARCH_SCOPES.map((candidate) => candidate.value).filter((value) => value !== scope)];

	return { kind, scope, bestMatch, order, isPicked: picked !== null };
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

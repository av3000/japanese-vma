import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import { KanjiIndexJlpt } from '@/api/generated/model/kanjiIndexJlpt';
import {
	emptyArticleListFilterState,
	serializeArticleListFilterState,
} from '@/routes/ArticlesList/articleListSearchParams';

export const SEARCH_SCOPES = [
	{ value: 'articles', label: 'Articles', path: '/articles' },
	{ value: 'kanji', label: 'Kanji', path: '/kanjis' },
	{ value: 'words', label: 'Words', path: '/words' },
	{ value: 'sentences', label: 'Sentences', path: '/sentences' },
	{ value: 'radicals', label: 'Radicals', path: '/radicals' },
] as const;

export type SearchScope = (typeof SEARCH_SCOPES)[number]['value'];

export const DEFAULT_SEARCH_SCOPE: SearchScope = 'articles';

const SCOPE_VALUES = new Set<string>(SEARCH_SCOPES.map((scope) => scope.value));

export const isSearchScope = (value: unknown): value is SearchScope =>
	typeof value === 'string' && SCOPE_VALUES.has(value);

export const findSearchScope = (scope: SearchScope) =>
	SEARCH_SCOPES.find((candidate) => candidate.value === scope) ?? SEARCH_SCOPES[0];

export interface RefineOption {
	value: string;
	label: string;
}

/**
 * The "Refine" chips for one scope. `multiple: false` is a single choice with an "Any" chip that
 * clears it; `multiple: true` toggles each option independently.
 */
export interface ScopeRefine {
	label: string;
	multiple: boolean;
	options: readonly RefineOption[];
}

/** The same values and labels as `KanjiFilters`, in the panel's N5-first order. */
const KANJI_JLPT_OPTIONS: readonly RefineOption[] = [
	{ value: KanjiIndexJlpt.NUMBER_5, label: 'N5' },
	{ value: KanjiIndexJlpt.NUMBER_4, label: 'N4' },
	{ value: KanjiIndexJlpt.NUMBER_3, label: 'N3' },
	{ value: KanjiIndexJlpt.NUMBER_2, label: 'N2' },
	{ value: KanjiIndexJlpt.NUMBER_1, label: 'N1' },
	{ value: KanjiIndexJlpt['-'], label: 'Uncommon' },
];

const ARTICLE_JLPT_OPTIONS: ReadonlyArray<{ value: ArticleIndexJlptLevelsItem; label: string }> = [
	{ value: 'n5', label: 'N5' },
	{ value: 'n4', label: 'N4' },
	{ value: 'n3', label: 'N3' },
	{ value: 'n2', label: 'N2' },
	{ value: 'n1', label: 'N1' },
];

/**
 * Only scopes whose list endpoint takes the parameter get a refine row. Sentences and Radicals
 * accept only `keyword`. Words accepts `jlpt`, but every word has `jlpt = "-"` until the data is
 * backfilled (#399); that issue adds `words: { label: 'JLPT level', multiple: false, options:
 * KANJI_JLPT_OPTIONS }` here once WordsList reads the parameter.
 */
export const SCOPE_REFINES: Partial<Record<SearchScope, ScopeRefine>> = {
	kanji: { label: 'JLPT level', multiple: false, options: KANJI_JLPT_OPTIONS },
	articles: { label: 'JLPT levels', multiple: true, options: ARTICLE_JLPT_OPTIONS },
};

/** Keeps only values the scope's refine row offers, in the row's order, without duplicates. */
export const normalizeRefine = (scope: SearchScope, values: readonly string[]): string[] => {
	const refine = SCOPE_REFINES[scope];
	if (!refine) return [];
	const picked = refine.options.map((option) => option.value).filter((value) => values.includes(value));
	return refine.multiple ? picked : picked.slice(0, 1);
};

/**
 * The list URL a search lands on. The keyword is trimmed; an empty keyword opens the scope's list
 * unfiltered. Articles go through the route's own serializer so the Header and the list agree on
 * one URL shape; the list applies its own minimum search length. Refine values the scope does not
 * offer are dropped, so without refine the URL is exactly the one the landing search built.
 */
export const scopedSearchUrl = (scope: SearchScope, keyword: string, refine: readonly string[] = []): string => {
	const { path } = findSearchScope(scope);
	const trimmed = keyword.trim();
	const levels = normalizeRefine(scope, refine);

	let params: URLSearchParams;
	if (scope === 'articles') {
		params = serializeArticleListFilterState({
			...emptyArticleListFilterState(),
			q: trimmed,
			jlptLevels: levels as ArticleIndexJlptLevelsItem[],
		});
	} else {
		params = new URLSearchParams(trimmed ? { keyword: trimmed } : {});
		if (levels[0]) params.set('jlpt', levels[0]);
	}

	const query = params.toString();
	return query ? `${path}?${query}` : path;
};

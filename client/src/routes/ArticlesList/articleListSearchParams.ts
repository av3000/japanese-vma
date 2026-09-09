import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexParams } from '@/api/generated/model/articleIndexParams';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';

/**
 * URL <-> filter state for the Articles route.
 *
 * The URL is the single source of truth. Everything the list renders can be
 * reconstructed from it, which is what makes a filtered list shareable, restorable
 * on refresh, and correct under browser back/forward.
 *
 * Kept in the route layer rather than `src/api/`: `src/api/` is the generated-client
 * and server-state boundary, and this is route state that happens to feed a request.
 */

export const DEFAULT_PER_PAGE = 12;

export const DEFAULT_SORT: ArticleIndexSort = '-created_at';

/**
 * One label per value of the generated sort union, in dropdown order. Typed as a
 * Record so a backend addition fails typecheck here instead of silently missing
 * from the dropdown.
 */
const SORT_LABELS: Record<ArticleIndexSort, string> = {
	'-created_at': 'Newest first',
	created_at: 'Oldest first',
	'-updated_at': 'Recently updated',
	updated_at: 'Least recently updated',
	title_jp: 'Title (JP) A-Z',
	'-title_jp': 'Title (JP) Z-A',
	title_en: 'Title (EN) A-Z',
	'-title_en': 'Title (EN) Z-A',
};

export const SORT_OPTIONS: ReadonlyArray<{ value: ArticleIndexSort; label: string }> = (
	Object.keys(SORT_LABELS) as ArticleIndexSort[]
).map((value) => ({ value, label: SORT_LABELS[value] }));

const SORT_VALUES = new Set<string>(SORT_OPTIONS.map((option) => option.value));

export const JLPT_LEVELS: ReadonlyArray<ArticleIndexJlptLevelsItem> = ['n5', 'n4', 'n3', 'n2', 'n1', 'uncommon'];

const JLPT_VALUES = new Set<string>(JLPT_LEVELS);

/**
 * Backend rejects a shorter search (SearchTerm::MIN_LENGTH), so do not spend a
 * request finding out. The single frontend definition; the filter form imports it.
 */
export const MIN_SEARCH_LENGTH = 2;

export type ArticleListFilterState = {
	q: string;
	jlptLevels: ArticleIndexJlptLevelsItem[];
	hashtagIds: number[];
	sort: ArticleIndexSort;
	page: number;
};

export const emptyArticleListFilterState = (): ArticleListFilterState => ({
	q: '',
	jlptLevels: [],
	hashtagIds: [],
	sort: DEFAULT_SORT,
	page: 1,
});

/**
 * Arrays are deduplicated and sorted before they reach a request or a React Query
 * key. Without it, ?jlpt_levels[]=n5&jlpt_levels[]=n4 and the reverse order are two
 * cache entries for one result set.
 */
const normalizeJlptLevels = (values: string[]): ArticleIndexJlptLevelsItem[] =>
	Array.from(new Set(values.filter((value): value is ArticleIndexJlptLevelsItem => JLPT_VALUES.has(value)))).sort(
		(a, b) => JLPT_LEVELS.indexOf(a) - JLPT_LEVELS.indexOf(b),
	);

const normalizeHashtagIds = (values: Array<string | number>): number[] =>
	Array.from(
		new Set(
			values
				.map((value) => (typeof value === 'number' ? value : Number.parseInt(value, 10)))
				.filter((value) => Number.isInteger(value) && value > 0),
		),
	).sort((a, b) => a - b);

const readArray = (searchParams: URLSearchParams, key: string): string[] => [
	// Accept both `key` and PHP-style `key[]` so a hand-edited or shared URL works.
	...searchParams.getAll(key),
	...searchParams.getAll(`${key}[]`),
];

const readPage = (raw: string | null): number => {
	const parsed = Number.parseInt(raw ?? '', 10);

	return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

export const parseArticleListSearchParams = (searchParams: URLSearchParams): ArticleListFilterState => {
	const q = searchParams.get('q')?.trim() ?? '';
	const sort = searchParams.get('sort') ?? '';

	return {
		q: q.length >= MIN_SEARCH_LENGTH ? q : '',
		jlptLevels: normalizeJlptLevels(readArray(searchParams, 'jlpt_levels')),
		hashtagIds: normalizeHashtagIds(readArray(searchParams, 'hashtag_ids')),
		sort: SORT_VALUES.has(sort) ? (sort as ArticleIndexSort) : DEFAULT_SORT,
		page: readPage(searchParams.get('page')),
	};
};

/**
 * Defaults and empty values are omitted so a pristine list has a clean URL and two
 * equivalent states never produce two different URLs.
 */
export const serializeArticleListFilterState = (state: ArticleListFilterState): URLSearchParams => {
	const params = new URLSearchParams();

	if (state.q !== '') {
		params.set('q', state.q);
	}

	for (const level of normalizeJlptLevels(state.jlptLevels)) {
		params.append('jlpt_levels[]', level);
	}

	for (const hashtagId of normalizeHashtagIds(state.hashtagIds)) {
		params.append('hashtag_ids[]', String(hashtagId));
	}

	if (state.sort !== DEFAULT_SORT) {
		params.set('sort', state.sort);
	}

	if (state.page > 1) {
		params.set('page', String(state.page));
	}

	return params;
};

/**
 * Map route state onto generated request parameters.
 *
 * `page` is deliberately excluded: the infinite query owns it as its page param, so
 * including it here would put it in the cache key twice and split the cache per page.
 */
export const mapArticleFiltersToGeneratedParams = (
	state: ArticleListFilterState,
	options: { includeFacets?: boolean } = {},
): Omit<ArticleIndexParams, 'page'> => ({
	...(state.q !== '' ? { q: state.q } : {}),
	...(state.jlptLevels.length > 0 ? { 'jlpt_levels[]': normalizeJlptLevels(state.jlptLevels) } : {}),
	...(state.hashtagIds.length > 0 ? { 'hashtag_ids[]': normalizeHashtagIds(state.hashtagIds) } : {}),
	sort: state.sort,
	per_page: DEFAULT_PER_PAGE,
	include_stats_counts: true,
	include_kanjis: true,
	include_facets: options.includeFacets ?? false,
});

/**
 * Changing what is being searched invalidates which page the user is on: page 4 of
 * the old result set is meaningless against the new one.
 */
export const resetPage = (state: ArticleListFilterState): ArticleListFilterState => ({ ...state, page: 1 });

export const toggleJlptLevel = (
	state: ArticleListFilterState,
	level: ArticleIndexJlptLevelsItem,
): ArticleListFilterState =>
	resetPage({
		...state,
		jlptLevels: state.jlptLevels.includes(level)
			? state.jlptLevels.filter((value) => value !== level)
			: [...state.jlptLevels, level],
	});

export const toggleHashtagId = (state: ArticleListFilterState, hashtagId: number): ArticleListFilterState =>
	resetPage({
		...state,
		hashtagIds: state.hashtagIds.includes(hashtagId)
			? state.hashtagIds.filter((value) => value !== hashtagId)
			: [...state.hashtagIds, hashtagId],
	});

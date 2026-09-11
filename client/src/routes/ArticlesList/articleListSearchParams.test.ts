import { describe, expect, it } from 'vitest';
import {
	DEFAULT_PER_PAGE,
	DEFAULT_SORT,
	emptyArticleListFilterState,
	mapArticleFiltersToGeneratedParams,
	parseArticleListSearchParams,
	resetPage,
	serializeArticleListFilterState,
	toggleHashtagId,
	toggleJlptLevel,
	type ArticleListFilterState,
} from './articleListSearchParams';

const parse = (query: string) => parseArticleListSearchParams(new URLSearchParams(query));
const serialize = (state: ArticleListFilterState) => serializeArticleListFilterState(state).toString();

describe('parseArticleListSearchParams', () => {
	it('returns the default state for an empty URL', () => {
		expect(parse('')).toEqual(emptyArticleListFilterState());
	});

	it('reads every supported dimension', () => {
		expect(parse('q=grammar&jlpt_levels[]=n2&hashtag_ids[]=7&sort=title_jp&page=3')).toEqual({
			q: 'grammar',
			jlptLevels: ['n2'],
			hashtagIds: [7],
			sort: 'title_jp',
			page: 3,
		});
	});

	it('accepts array keys with and without the PHP bracket suffix', () => {
		expect(parse('jlpt_levels=n5&jlpt_levels[]=n4').jlptLevels).toEqual(['n5', 'n4']);
	});

	/**
	 * Two URLs describing the same selection must produce one cache key, otherwise
	 * the same result set is fetched and stored twice.
	 */
	it('deduplicates and orders multi-value filters', () => {
		expect(parse('jlpt_levels[]=n2&jlpt_levels[]=n5&jlpt_levels[]=n2').jlptLevels).toEqual(['n5', 'n2']);
		expect(parse('hashtag_ids[]=9&hashtag_ids[]=3&hashtag_ids[]=9').hashtagIds).toEqual([3, 9]);
	});

	it('drops values the backend would reject rather than sending them', () => {
		expect(parse('jlpt_levels[]=n9&jlpt_levels[]=n5').jlptLevels).toEqual(['n5']);
		expect(parse('hashtag_ids[]=abc&hashtag_ids[]=0&hashtag_ids[]=-2&hashtag_ids[]=4').hashtagIds).toEqual([4]);
		expect(parse('sort=views_total').sort).toBe(DEFAULT_SORT);
	});

	/**
	 * The backend rejects a one-character q with a 422. Treating it as absent avoids
	 * spending a request to be told so.
	 */
	it('ignores a search too short for the backend to accept', () => {
		expect(parse('q=a').q).toBe('');
		expect(parse('q=ab').q).toBe('ab');
		expect(parse('q=%20%20').q).toBe('');
	});

	it('falls back to page 1 for a nonsensical page', () => {
		expect(parse('page=0').page).toBe(1);
		expect(parse('page=-4').page).toBe(1);
		expect(parse('page=abc').page).toBe(1);
	});
});

describe('serializeArticleListFilterState', () => {
	it('omits defaults so a pristine list has a clean URL', () => {
		expect(serialize(emptyArticleListFilterState())).toBe('');
	});

	it('omits the default sort and page but keeps explicit ones', () => {
		expect(serialize({ ...emptyArticleListFilterState(), sort: DEFAULT_SORT, page: 1 })).toBe('');
		expect(serialize({ ...emptyArticleListFilterState(), sort: 'title_jp' })).toContain('sort=title_jp');
		expect(serialize({ ...emptyArticleListFilterState(), page: 2 })).toContain('page=2');
	});

	it('round-trips canonical state', () => {
		const state: ArticleListFilterState = {
			q: 'grammar',
			jlptLevels: ['n5', 'n2'],
			hashtagIds: [3, 9],
			sort: 'title_en',
			page: 4,
		};

		expect(parseArticleListSearchParams(serializeArticleListFilterState(state))).toEqual(state);
	});

	it('produces the same URL regardless of the order values were added', () => {
		const a = serialize({ ...emptyArticleListFilterState(), jlptLevels: ['n2', 'n5'], hashtagIds: [9, 3] });
		const b = serialize({ ...emptyArticleListFilterState(), jlptLevels: ['n5', 'n2'], hashtagIds: [3, 9] });

		expect(a).toBe(b);
	});
});

describe('mapArticleFiltersToGeneratedParams', () => {
	it('sends canonical keys, never category or views_total', () => {
		const params = mapArticleFiltersToGeneratedParams({
			...emptyArticleListFilterState(),
			q: 'grammar',
			jlptLevels: ['n5'],
			hashtagIds: [3],
			sort: 'title_jp',
		});

		expect(params).toMatchObject({
			q: 'grammar',
			'jlpt_levels[]': ['n5'],
			'hashtag_ids[]': [3],
			sort: 'title_jp',
			per_page: DEFAULT_PER_PAGE,
		});
		expect(params).not.toHaveProperty('category');
		expect(params).not.toHaveProperty('sort_by');
		expect(params).not.toHaveProperty('search');
	});

	it('omits empty filters instead of sending empty arrays', () => {
		const params = mapArticleFiltersToGeneratedParams(emptyArticleListFilterState());

		expect(params).not.toHaveProperty('q');
		expect(params).not.toHaveProperty('jlpt_levels[]');
		expect(params).not.toHaveProperty('hashtag_ids[]');
	});

	/**
	 * page belongs to the infinite query's page param. Including it here would put it
	 * in the cache key twice and split the cache per page.
	 */
	it('never includes page', () => {
		expect(mapArticleFiltersToGeneratedParams({ ...emptyArticleListFilterState(), page: 5 })).not.toHaveProperty(
			'page',
		);
	});

	it('does not request facets unless asked', () => {
		expect(mapArticleFiltersToGeneratedParams(emptyArticleListFilterState()).include_facets).toBe(false);
		expect(
			mapArticleFiltersToGeneratedParams(emptyArticleListFilterState(), { includeFacets: true }).include_facets,
		).toBe(true);
	});
});

describe('filter transitions', () => {
	const onPageFour: ArticleListFilterState = { ...emptyArticleListFilterState(), page: 4 };

	it('resets the page when a filter changes', () => {
		expect(toggleJlptLevel(onPageFour, 'n5').page).toBe(1);
		expect(toggleHashtagId(onPageFour, 3).page).toBe(1);
		expect(resetPage(onPageFour).page).toBe(1);
	});

	it('toggles a JLPT level on and back off', () => {
		const selected = toggleJlptLevel(emptyArticleListFilterState(), 'n5');
		expect(selected.jlptLevels).toEqual(['n5']);
		expect(toggleJlptLevel(selected, 'n5').jlptLevels).toEqual([]);
	});

	it('toggles a hashtag on and back off', () => {
		const selected = toggleHashtagId(emptyArticleListFilterState(), 3);
		expect(selected.hashtagIds).toEqual([3]);
		expect(toggleHashtagId(selected, 3).hashtagIds).toEqual([]);
	});

	it('keeps other dimensions untouched when one toggles', () => {
		const state: ArticleListFilterState = {
			...emptyArticleListFilterState(),
			q: 'grammar',
			hashtagIds: [3],
			sort: 'title_jp',
		};

		const next = toggleJlptLevel(state, 'n5');

		expect(next.q).toBe('grammar');
		expect(next.hashtagIds).toEqual([3]);
		expect(next.sort).toBe('title_jp');
	});
});

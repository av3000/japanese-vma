import { describe, expect, it } from 'vitest';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
// Statically imported: a dynamic import here raced the first-run transform and
// intermittently blew the 5s default timeout without testing anything extra.
import * as articlesModule from './useInfiniteArticles';

const createPage = (overrides?: Partial<ArticleListResource>): ArticleListResource => ({
	items: [],
	// Always present, empty when facets were not requested.
	facets: [],
	query: {
		q: null,
		filters: { jlpt_levels: [], hashtag_ids: [], kanji_ids: [], word_ids: [], author_uid: null },
		sort: '-created_at',
	},
	pagination: {
		page: 2,
		per_page: 12,
		total: 42,
		last_page: 4,
		has_more: true,
	},
	...overrides,
});

describe('useInfiniteArticles helpers', () => {
	it('derives the next numeric page from typed pagination metadata', () => {
		const module = articlesModule;

		expect(module.getNextArticlesPageParam(createPage())).toBe(3);
		expect(
			module.getNextArticlesPageParam(
				createPage({
					pagination: {
						page: 4,
						per_page: 12,
						total: 42,
						last_page: 4,
						has_more: false,
					},
				}),
			),
		).toBeUndefined();
	});

	it('reads the total from the top-level pagination resource', () => {
		const module = articlesModule;

		expect(module.getArticlesTotal([createPage()])).toBe(42);
		expect(module.getArticlesTotal([])).toBe(0);
	});
});

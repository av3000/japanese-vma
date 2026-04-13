import { describe, expect, it } from 'vitest';
import type { ArticleListResponseData } from '@/api/generated/model/articleListResponseData';

const createPage = (overrides?: Partial<ArticleListResponseData>): ArticleListResponseData => ({
	items: [],
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
	it('derives the next numeric page from typed pagination metadata', async () => {
		const module = await import('./useInfiniteArticles');

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

	it('reads the total from the top-level pagination resource', async () => {
		const module = await import('./useInfiniteArticles');

		expect(module.getArticlesTotal([createPage()])).toBe(42);
		expect(module.getArticlesTotal([])).toBe(0);
	});
});

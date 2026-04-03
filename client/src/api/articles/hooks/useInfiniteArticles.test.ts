import { describe, expect, it } from 'vitest';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';

const createPage = (overrides?: Partial<ArticleListResource>): ArticleListResource => ({
	items: [],
	pagination: {
		page: '2',
		per_page: '12',
		total: '42',
		last_page: '4',
		has_more: '1',
	},
	...overrides,
});

describe('useInfiniteArticles helpers', () => {
	it('derives the next numeric page from string pagination metadata', async () => {
		const module = await import('./useInfiniteArticles');

		expect(module.getNextArticlesPageParam(createPage())).toBe(3);
		expect(
			module.getNextArticlesPageParam(
				createPage({
					pagination: {
						page: '4',
						per_page: '12',
						total: '42',
						last_page: '4',
						has_more: '0',
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

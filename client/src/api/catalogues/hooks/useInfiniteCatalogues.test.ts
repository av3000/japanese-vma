import { describe, expect, it } from 'vitest';
import type { CatalogueListResource } from '@/api/generated/model/catalogueListResource';
import { getCataloguesTotal, getNextCataloguesPageParam } from './useInfiniteCatalogues';

const createPage = (overrides?: Partial<CatalogueListResource>): CatalogueListResource => ({
	items: [],
	pagination: {
		page: 2,
		per_page: 12,
		total: 25,
		last_page: 3,
		has_more: true,
	},
	...overrides,
});

describe('useInfiniteCatalogues helpers', () => {
	it('derives the next page from typed pagination metadata', () => {
		expect(getNextCataloguesPageParam(createPage())).toBe(3);
		expect(
			getNextCataloguesPageParam(
				createPage({
					pagination: {
						page: 3,
						per_page: 12,
						total: 25,
						last_page: 3,
						has_more: false,
					},
				}),
			),
		).toBeUndefined();
	});

	it('reads totals from the first page pagination metadata', () => {
		expect(getCataloguesTotal([createPage()])).toBe(25);
		expect(getCataloguesTotal([])).toBe(0);
	});
});

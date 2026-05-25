import { describe, expect, it } from 'vitest';
import type { RadicalIndex200 } from '@/api/generated/model/radicalIndex200';
import {
	getInfiniteRadicalsQueryKey,
	getNextRadicalsPageParam,
	getRadicalsTotal,
} from './useInfiniteRadicals';

const page = (overrides: Partial<RadicalIndex200> = {}): RadicalIndex200 => ({
	items: [],
	pagination: {
		page: 1,
		per_page: 12,
		total: 25,
		last_page: 3,
		has_more: true,
	},
	...overrides,
});

describe('useInfiniteRadicals helpers', () => {
	it('uses generated radical index key with filters', () => {
		expect(getInfiniteRadicalsQueryKey({ keyword: 'water', strokes: 4 })).toEqual([
			'/radicals',
			{ keyword: 'water', strokes: 4 },
		]);
	});

	it('returns the next page while the backend says more pages exist', () => {
		expect(getNextRadicalsPageParam(page())).toBe(2);
	});

	it('stops pagination when the backend has no more pages', () => {
		expect(
			getNextRadicalsPageParam(
				page({
					pagination: {
						page: 2,
						per_page: 12,
						total: 25,
						last_page: 2,
						has_more: false,
					},
				}),
			),
		).toBeUndefined();
	});

	it('reads the total from the first loaded page', () => {
		expect(getRadicalsTotal([page()])).toBe(25);
		expect(getRadicalsTotal(undefined)).toBe(0);
	});
});

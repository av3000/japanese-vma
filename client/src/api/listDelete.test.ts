import type { InfiniteData } from '@tanstack/react-query';
import { describe, expect, it } from 'vitest';
import { removeFromInfinitePages } from './listDelete';

type Page = { items: Array<{ uuid: string; title: string }>; pagination: { total: number } };

const cache = (...pages: string[][]): InfiniteData<Page> => ({
	pages: pages.map((uuids) => ({
		items: uuids.map((uuid) => ({ uuid, title: `Title ${uuid}` })),
		pagination: { total: uuids.length },
	})),
	pageParams: pages.map((_, index) => index + 1),
});

const uuidsOf = (data: InfiniteData<Page> | undefined) =>
	data?.pages.map((page) => page.items.map((item) => item.uuid));

describe('removeFromInfinitePages', () => {
	it('drops the item from every page and keeps the rest of each page', () => {
		const result = removeFromInfinitePages(cache(['a', 'b'], ['c', 'b']), 'b');

		expect(uuidsOf(result)).toEqual([['a'], ['c']]);
		expect(result?.pages[0].items[0].title).toBe('Title a');
		expect(result?.pageParams).toEqual([1, 2]);
	});

	it('leaves totals alone; the refetch settles them', () => {
		expect(removeFromInfinitePages(cache(['a', 'b']), 'a')?.pages[0].pagination.total).toBe(2);
	});

	it('leaves an empty cache alone', () => {
		expect(removeFromInfinitePages<Page>(undefined, 'a')).toBeUndefined();
	});
});

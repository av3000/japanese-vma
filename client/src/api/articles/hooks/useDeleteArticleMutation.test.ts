import { QueryClient, type InfiniteData } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';
import { dashboardKeys } from '@/api/dashboard/keys';
import { articleDestroy } from '@/api/generated/article/article';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { makeDashboardArticle } from '@/components/features/dashboard/dashboardFixtures';
import { articleKeys } from '../keys';
import { createDeleteArticleMutationOptions } from './useDeleteArticleMutation';

vi.mock('@/api/generated/article/article', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/article/article')>(
		'@/api/generated/article/article',
	);
	return { ...actual, articleDestroy: vi.fn() };
});

const page = (uuids: string[]): ArticleListResource => ({
	items: uuids.map((uuid) => makeDashboardArticle({ uuid })),
	facets: [],
	applied: {} as ArticleListResource['applied'],
	pagination: { page: 1, per_page: 25, total: uuids.length, last_page: 1, has_more: false },
});

const cache = (...pages: string[][]): InfiniteData<ArticleListResource> => ({
	pages: pages.map(page),
	pageParams: pages.map((_, index) => index + 1),
});

const uuidsOf = (data: InfiniteData<ArticleListResource> | undefined) =>
	data?.pages.map((p) => p.items.map((item) => item.uuid));

describe('createDeleteArticleMutationOptions', () => {
	it('calls the generated destroy with the uuid', async () => {
		vi.mocked(articleDestroy).mockResolvedValue({} as never);
		const options = createDeleteArticleMutationOptions(new QueryClient());

		await options.mutationFn?.('article-uuid', undefined as never);

		expect(articleDestroy).toHaveBeenCalledWith('article-uuid');
	});

	it('removes the row everywhere, drops the detail and refetches lists and counts', () => {
		const queryClient = new QueryClient();
		const listKey = articleKeys.list({ author_uid: 'owner', per_page: 25 });
		queryClient.setQueryData(listKey, cache(['a', 'gone'], ['gone', 'b']));
		queryClient.setQueryData(articleKeys.detail('gone'), makeDashboardArticle({ uuid: 'gone' }));
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');

		createDeleteArticleMutationOptions(queryClient).onSuccess?.(undefined, 'gone', undefined, undefined as never);

		expect(uuidsOf(queryClient.getQueryData(listKey))).toEqual([['a'], ['b']]);
		expect(queryClient.getQueryData(articleKeys.detail('gone'))).toBeUndefined();
		expect(invalidate).toHaveBeenCalledWith({ queryKey: articleKeys.lists() });
		expect(invalidate).toHaveBeenCalledWith({ queryKey: dashboardKeys.all });
	});
});

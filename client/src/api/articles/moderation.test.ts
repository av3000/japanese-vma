import type { InfiniteData } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { articleSetStatus, getArticlePendingQueryKey } from '@/api/generated/article/article';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleModerationItemResource } from '@/api/generated/model/articleModerationItemResource';
import type { ArticleModerationListResource } from '@/api/generated/model/articleModerationListResource';
import type { ArticleStatusResource } from '@/api/generated/model/articleStatusResource';
import { getArticleDetailQueryKey } from './details';
import {
	ARTICLE_MODERATION_CHOICES,
	ARTICLE_STATUS,
	applyStatusToArticleDetail,
	applyStatusToPendingArticles,
	createArticleStatusMutationOptions,
	getNextPendingArticlesPageParam,
	getPendingArticlesQueryKey,
	getPendingArticlesTotal,
	isModerationQueueStatus,
} from './moderation';

vi.mock('@/api/generated/article/article', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/article/article')>(
		'@/api/generated/article/article',
	);
	return {
		...actual,
		articleSetStatus: vi.fn(),
	};
});

const createItem = (overrides: Partial<ArticleModerationItemResource> = {}): ArticleModerationItemResource => ({
	uuid: 'article-uuid',
	title_jp: '保留中の記事',
	status: ARTICLE_STATUS.PENDING,
	status_label: 'Pending',
	hashtags: [],
	created_at: '2026-05-04T09:00:00+00:00',
	...overrides,
});

const createPage = (overrides: Partial<ArticleModerationListResource> = {}): ArticleModerationListResource => ({
	items: [createItem()],
	pagination: {
		page: 1,
		per_page: 20,
		total: 42,
		last_page: 3,
		has_more: true,
	},
	...overrides,
});

const createPendingCache = (pages: ArticleModerationListResource[]): InfiniteData<ArticleModerationListResource> => ({
	pages,
	pageParams: pages.map((_, index) => index + 1),
});

const createPersisted = (overrides: Partial<ArticleStatusResource> = {}): ArticleStatusResource => ({
	uuid: 'article-uuid',
	status: ARTICLE_STATUS.APPROVED,
	status_label: 'Approved',
	...overrides,
});

describe('article moderation vocabulary', () => {
	it('binds the admin review choices to the v1 ArticleStatus values', () => {
		expect(ARTICLE_MODERATION_CHOICES).toEqual([
			{ value: 0, label: 'Pending' },
			{ value: 2, label: 'Review' },
			{ value: 3, label: 'Reject' },
			{ value: 4, label: 'Approve' },
		]);
	});

	it('treats only pending and reviewing as queue statuses, matching findModerationQueue', () => {
		expect(isModerationQueueStatus(ARTICLE_STATUS.PENDING)).toBe(true);
		expect(isModerationQueueStatus(ARTICLE_STATUS.REVIEWING)).toBe(true);
		expect(isModerationQueueStatus(ARTICLE_STATUS.PROCESSED)).toBe(false);
		expect(isModerationQueueStatus(ARTICLE_STATUS.REJECTED)).toBe(false);
		expect(isModerationQueueStatus(ARTICLE_STATUS.APPROVED)).toBe(false);
	});
});

describe('pending articles query helpers', () => {
	it('reuses the generated pending query key instead of a handwritten path', () => {
		expect(getPendingArticlesQueryKey()).toEqual(getArticlePendingQueryKey({}));
		expect(getPendingArticlesQueryKey()[0]).toBe('/articles/pending');
	});

	it('derives the next page from typed pagination metadata', () => {
		expect(getNextPendingArticlesPageParam(createPage())).toBe(2);
		expect(
			getNextPendingArticlesPageParam(
				createPage({ pagination: { page: 3, per_page: 20, total: 42, last_page: 3, has_more: false } }),
			),
		).toBeUndefined();
	});

	it('reads the total from the first page only', () => {
		expect(getPendingArticlesTotal([createPage()])).toBe(42);
		expect(getPendingArticlesTotal([])).toBe(0);
		expect(getPendingArticlesTotal(undefined)).toBe(0);
	});
});

describe('applyStatusToArticleDetail', () => {
	const detail = { uid: 'article-uuid', title_jp: 'Study', status: ARTICLE_STATUS.PENDING } as ArticleDetailResource;

	it('writes the persisted status onto the cached detail without touching other fields', () => {
		const next = applyStatusToArticleDetail(detail, createPersisted());

		expect(next).toEqual({ uid: 'article-uuid', title_jp: 'Study', status: ARTICLE_STATUS.APPROVED });
		expect(detail.status).toBe(ARTICLE_STATUS.PENDING);
	});

	it('leaves an unseeded cache alone', () => {
		expect(applyStatusToArticleDetail(undefined, createPersisted())).toBeUndefined();
	});
});

describe('applyStatusToPendingArticles', () => {
	it('drops the article from every page once its status leaves the queue', () => {
		const cache = createPendingCache([
			createPage({ items: [createItem(), createItem({ uuid: 'other-uuid' })] }),
			createPage({ items: [createItem({ uuid: 'second-page-uuid' })] }),
		]);

		const next = applyStatusToPendingArticles(cache, createPersisted());

		expect(next?.pages[0].items.map((item) => item.uuid)).toEqual(['other-uuid']);
		expect(next?.pages[1].items.map((item) => item.uuid)).toEqual(['second-page-uuid']);
	});

	it('updates the row in place while the article is still reviewable', () => {
		const cache = createPendingCache([createPage()]);

		const next = applyStatusToPendingArticles(
			cache,
			createPersisted({ status: ARTICLE_STATUS.REVIEWING, status_label: 'Under Review' }),
		);

		expect(next?.pages[0].items).toEqual([
			createItem({ status: ARTICLE_STATUS.REVIEWING, status_label: 'Under Review' }),
		]);
	});

	it('leaves an unseeded cache alone', () => {
		expect(applyStatusToPendingArticles(undefined, createPersisted())).toBeUndefined();
	});
});

describe('createArticleStatusMutationOptions', () => {
	const setQueryData = vi.fn();
	const invalidateQueries = vi.fn();
	const queryClient = { setQueryData, invalidateQueries } as never;
	// react-query hands callbacks a MutationFunctionContext we do not exercise here.
	const mutationContext = {} as never;

	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('submits the status to the generated v1 endpoint using UUID identity', async () => {
		vi.mocked(articleSetStatus).mockResolvedValue(createPersisted());

		const options = createArticleStatusMutationOptions('article-uuid', queryClient);
		await options.mutationFn?.(ARTICLE_STATUS.APPROVED, mutationContext);

		expect(articleSetStatus).toHaveBeenCalledWith('article-uuid', { status: ARTICLE_STATUS.APPROVED });
	});

	it('reconciles the detail and pending caches from the persisted response', () => {
		const onSuccess = vi.fn();
		const persisted = createPersisted();

		const options = createArticleStatusMutationOptions('article-uuid', queryClient, { onSuccess });
		options.onSuccess?.(persisted, ARTICLE_STATUS.APPROVED, undefined, mutationContext);

		expect(setQueryData).toHaveBeenNthCalledWith(1, getArticleDetailQueryKey('article-uuid'), expect.any(Function));
		expect(setQueryData).toHaveBeenNthCalledWith(2, getPendingArticlesQueryKey(), expect.any(Function));
		expect(invalidateQueries).toHaveBeenCalledWith({ queryKey: getPendingArticlesQueryKey() });
		expect(onSuccess).toHaveBeenCalledWith(persisted);

		const detailUpdater = setQueryData.mock.calls[0][1] as (detail: ArticleDetailResource) => ArticleDetailResource;
		expect(detailUpdater({ status: ARTICLE_STATUS.PENDING } as ArticleDetailResource).status).toBe(
			ARTICLE_STATUS.APPROVED,
		);
	});

	it('retains the prior status on failure by writing no cache at all', () => {
		const onError = vi.fn();
		const error = new Error('Moderation failed');
		vi.spyOn(console, 'error').mockImplementation(() => {});

		const options = createArticleStatusMutationOptions('article-uuid', queryClient, { onError });
		options.onError?.(error, ARTICLE_STATUS.APPROVED, undefined, mutationContext);

		expect(setQueryData).not.toHaveBeenCalled();
		expect(invalidateQueries).not.toHaveBeenCalled();
		expect(onError).toHaveBeenCalledWith(error);
	});
});

import { describe, expect, it, vi } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import { articleIndex } from '@/api/generated/article/article';
import { catalogueIndex } from '@/api/generated/catalogue/catalogue';
import { fetchDashboardCounts } from './useDashboardCounts';

vi.mock('@/api/generated/article/article', () => ({ articleIndex: vi.fn() }));
vi.mock('@/api/generated/catalogue/catalogue', () => ({ catalogueIndex: vi.fn() }));

const page = (total: number) => ({
	items: [],
	pagination: { page: 1, per_page: 1, total, last_page: 1, has_more: false },
});

describe('fetchDashboardCounts', () => {
	it('reads three unfiltered totals with one-row requests', async () => {
		vi.mocked(articleIndex)
			.mockResolvedValueOnce(page(12) as never)
			.mockResolvedValueOnce(page(2) as never);
		vi.mocked(catalogueIndex).mockResolvedValueOnce(page(6) as never);
		const awaiting = [ARTICLE_STATUS.PENDING, ARTICLE_STATUS.REVIEWING];

		await expect(fetchDashboardCounts('owner-uuid', awaiting)).resolves.toEqual({
			articles: 12,
			lists: 6,
			awaitingReview: 2,
		});

		expect(articleIndex).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining({ author_uid: 'owner-uuid', per_page: 1, include_stats_counts: false }),
			undefined,
			undefined,
		);
		expect(vi.mocked(articleIndex).mock.calls[0][0]).not.toHaveProperty('statuses[]');
		expect(articleIndex).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining({ author_uid: 'owner-uuid', per_page: 1, 'statuses[]': awaiting }),
			undefined,
			undefined,
		);
		expect(catalogueIndex).toHaveBeenCalledWith(
			expect.objectContaining({ owner_uid: 'owner-uuid', per_page: 1, custom_only: false, public_only: false }),
			undefined,
			undefined,
		);
	});
});

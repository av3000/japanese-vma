import { describe, expect, it } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import { dashboardArticleFilters } from './DashboardArticlesPanel';

describe('dashboardArticleFilters', () => {
	it('asks for the owner articles 25 at a time, with counts and without tags or facets', () => {
		expect(dashboardArticleFilters('owner-uuid', '', 'all')).toEqual({
			author_uid: 'owner-uuid',
			per_page: 25,
			include_stats_counts: true,
			include_hashtags: false,
			include_facets: false,
		});
	});

	it('maps each approval choice onto statuses[]', () => {
		expect(dashboardArticleFilters('o', '', 'awaiting')['statuses[]']).toEqual([
			ARTICLE_STATUS.PENDING,
			ARTICLE_STATUS.REVIEWING,
		]);
		expect(dashboardArticleFilters('o', '', 'rejected')['statuses[]']).toEqual([ARTICLE_STATUS.REJECTED]);
		expect(dashboardArticleFilters('o', '', 'approved')['statuses[]']).toEqual([ARTICLE_STATUS.APPROVED]);
	});

	it('trims the keyword and drops one that is too short to send', () => {
		expect(dashboardArticleFilters('o', '  記事 ', 'all').q).toBe('記事');
		expect(dashboardArticleFilters('o', ' g ', 'all')).not.toHaveProperty('q');
	});
});

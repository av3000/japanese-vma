import { describe, expect, it } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import {
	ARTICLE_STATUS_FILTERS,
	dashboardTabSearch,
	defaultDashboardViewState,
	parseDashboardSearchParams,
	serializeDashboardViewState,
	statusesForFilter,
	visibleDashboardTabs,
	type DashboardViewState,
} from './dashboardSearchParams';

const parse = (query: string, isAdmin = false) => parseDashboardSearchParams(new URLSearchParams(query), { isAdmin });

const serialize = (state: DashboardViewState) => serializeDashboardViewState(state).toString();

describe('parseDashboardSearchParams', () => {
	it('opens Articles with no filters for a clean URL', () => {
		expect(parse('')).toEqual(defaultDashboardViewState('articles'));
	});

	it('reads the Articles keyword and status', () => {
		expect(parse('q=%E8%A8%98%E4%BA%8B&status=rejected')).toEqual({
			...defaultDashboardViewState('articles'),
			q: '記事',
			status: 'rejected',
		});
	});

	it('reads the Lists keyword, type and sort, and ignores the Articles status there', () => {
		expect(parse('tab=lists&q=n5&type=6&sort=pop&status=rejected')).toEqual({
			...defaultDashboardViewState('lists'),
			q: 'n5',
			listType: '6',
			listSort: 'pop',
		});
	});

	it('falls back to defaults for unknown values', () => {
		expect(parse('tab=settings&status=deleted')).toEqual(defaultDashboardViewState('articles'));
		expect(parse('tab=lists&type=2&sort=oldest')).toEqual(defaultDashboardViewState('lists'));
		expect(parse('tab=lists&type=banana')).toEqual(defaultDashboardViewState('lists'));
	});

	it('opens the review queue for admins only', () => {
		expect(parse('tab=review', true).tab).toBe('review');
		expect(parse('tab=review', false)).toEqual(defaultDashboardViewState('articles'));
	});

	it('carries no filters on the review queue', () => {
		expect(parse('tab=review&q=x&status=rejected', true)).toEqual(defaultDashboardViewState('review'));
	});
});

describe('serializeDashboardViewState', () => {
	it('writes nothing for the default view', () => {
		expect(serialize(defaultDashboardViewState())).toBe('');
	});

	it('writes only the active tab’s values', () => {
		expect(serialize({ tab: 'articles', q: 'kanji', status: 'awaiting', listType: '6', listSort: 'pop' })).toBe(
			'q=kanji&status=awaiting',
		);
		expect(serialize({ tab: 'lists', q: 'kanji', status: 'awaiting', listType: '6', listSort: 'pop' })).toBe(
			'tab=lists&q=kanji&type=6&sort=pop',
		);
	});

	it('round-trips every status choice and the lists filters', () => {
		for (const status of ARTICLE_STATUS_FILTERS) {
			const state = { ...defaultDashboardViewState('articles'), q: 'n3', status };
			expect(parse(serialize(state))).toEqual(state);
		}

		const lists = { ...defaultDashboardViewState('lists'), q: 'verbs', listType: '7', listSort: 'pop' as const };
		expect(parse(serialize(lists))).toEqual(lists);
	});
});

describe('statusesForFilter', () => {
	it('maps each choice onto the statuses[] request values', () => {
		expect(statusesForFilter('all')).toEqual([]);
		expect(statusesForFilter('awaiting')).toEqual([ARTICLE_STATUS.PENDING, ARTICLE_STATUS.REVIEWING]);
		expect(statusesForFilter('rejected')).toEqual([ARTICLE_STATUS.REJECTED]);
		expect(statusesForFilter('approved')).toEqual([ARTICLE_STATUS.APPROVED]);
	});
});

describe('tab links', () => {
	it('lists Review only for admins', () => {
		expect(visibleDashboardTabs(false)).toEqual(['articles', 'lists']);
		expect(visibleDashboardTabs(true)).toEqual(['articles', 'lists', 'review']);
	});

	it('links each tab with its own filters reset', () => {
		expect(dashboardTabSearch('articles')).toBe('');
		expect(dashboardTabSearch('lists')).toBe('?tab=lists');
		expect(dashboardTabSearch('review')).toBe('?tab=review');
	});
});

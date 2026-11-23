// @vitest-environment jsdom
import { act } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuth } from '@/hooks/useAuth';
import { controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import type { User } from '@/types';
import { renderDashboardRoute } from './dashboardTestRoute';

let capturedFilters: Record<string, unknown> | undefined;

vi.mock('@/hooks/useAuth', () => ({ useAuth: vi.fn() }));
vi.mock('@/api/dashboard/useDashboardCounts', () => ({ useDashboardCounts: () => ({ data: undefined }) }));
vi.mock('@/api/articles/hooks/useOwnerProcessingSubscription', () => ({ OwnerProcessingSubscription: () => null }));

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: ({ filters }: { filters: Record<string, unknown> }) => {
		capturedFilters = filters;

		return {
			articles: [],
			total: 0,
			isPending: false,
			isError: false,
			hasNextPage: false,
			isFetchingNextPage: false,
			fetchNextPage: vi.fn(),
			refetch: vi.fn(),
		};
	},
}));

const owner = { id: 7, uuid: 'owner-uuid', isAdmin: false } as User;

let route: Awaited<ReturnType<typeof renderDashboardRoute>>;

const advance = (ms: number) =>
	act(() => {
		vi.advanceTimersByTime(ms);
	});

const search = () => controlLabelled<HTMLInputElement>(route.view.container, 'Search your articles');

beforeEach(async () => {
	vi.useFakeTimers();
	capturedFilters = undefined;
	vi.mocked(useAuth).mockReturnValue({ isAuthenticated: true, isLoading: false, user: owner } as never);
	route = await renderDashboardRoute('/dashboard');
});

afterEach(async () => {
	await route.view.unmount();
	vi.useRealTimers();
});

describe('Dashboard articles search', () => {
	it('starts by listing the owner articles with no search term', () => {
		expect(capturedFilters).toMatchObject({ author_uid: 'owner-uuid', include_facets: false });
		expect(capturedFilters).not.toHaveProperty('q');
	});

	it('searches as the user types, after a 300 ms pause, and records it in the URL', () => {
		typeInto(search(), 'grammar');

		advance(299);
		expect(capturedFilters).not.toHaveProperty('q');
		expect(route.search()).toBe('');

		advance(1);
		expect(capturedFilters).toMatchObject({ q: 'grammar' });
		expect(route.search()).toBe('q=grammar');
	});

	it('sends the trimmed term as the canonical q, never the legacy search alias', () => {
		typeInto(search(), '  grammar ');
		advance(300);

		expect(capturedFilters).toMatchObject({ q: 'grammar' });
		expect(capturedFilters).not.toHaveProperty('search');
	});

	it('does not send a one-character term, which the backend would reject', () => {
		typeInto(search(), 'g');
		advance(300);

		expect(capturedFilters).not.toHaveProperty('q');
	});

	it('searches at once on Enter, without waiting out the pause, and does not reload the page', () => {
		typeInto(search(), 'grammar');

		const form = route.view.container.querySelector('form[role="search"]') as HTMLFormElement;
		const submitEvents: Event[] = [];
		form.addEventListener('submit', (event) => submitEvents.push(event));
		submitForm(form);

		expect(capturedFilters).toMatchObject({ q: 'grammar' });
		expect(submitEvents[0].defaultPrevented).toBe(true);
	});

	it('drops the term again when the field is cleared', () => {
		typeInto(search(), 'grammar');
		advance(300);
		typeInto(search(), '');
		advance(300);

		expect(capturedFilters).not.toHaveProperty('q');
		expect(route.search()).toBe('');
	});

	it('keeps typing out of the history, but follows the URL when it changes from outside', async () => {
		await route.navigate('/dashboard?status=approved');
		typeInto(search(), 'kanji');
		advance(300);

		expect(route.search()).toBe('q=kanji&status=approved');

		// Typing replaced the entry, so one step back leaves the filtered view entirely.
		await route.navigate(-1);

		expect(route.search()).toBe('');
		expect(search().value).toBe('');
		expect(capturedFilters).not.toHaveProperty('q');

		await route.navigate('/dashboard?q=restored');

		expect(search().value).toBe('restored');
		expect(capturedFilters).toMatchObject({ q: 'restored' });
	});
});

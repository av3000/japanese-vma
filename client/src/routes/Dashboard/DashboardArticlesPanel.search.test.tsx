// @vitest-environment jsdom
import type { ReactNode } from 'react';
import { act } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import type { User } from '@/types';
import DashboardArticlesPanel from './DashboardArticlesPanel';
import { DASHBOARD_TYPES } from './dashboard.constants';

let capturedFilters: Record<string, unknown> | undefined;

vi.mock('@/api/articles/moderation', () => ({
	usePendingArticles: () => ({ pendingArticles: [], total: 0, isPending: false, isError: false }),
}));

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: ({ filters }: { filters: Record<string, unknown> }) => {
		capturedFilters = filters;

		return {
			articles: [],
			total: 0,
			error: null,
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			status: 'success',
		};
	},
}));

vi.mock('@/api/articles/hooks/useOwnerProcessingSubscription', () => ({ OwnerProcessingSubscription: () => null }));
vi.mock('@/components/features/dashboard/DashboardArticleItem', () => ({ default: () => <div>item</div> }));
vi.mock('@/components/shared/Link', () => ({
	Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
}));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

const owner = { id: 7, uuid: 'owner-uuid', isAdmin: false } as User;

let view: Awaited<ReturnType<typeof renderWithAct>>;

const advance = (ms: number) =>
	act(() => {
		vi.advanceTimersByTime(ms);
	});

beforeEach(async () => {
	vi.useFakeTimers();
	capturedFilters = undefined;
	view = await renderWithAct(
		<DashboardArticlesPanel
			dashboardView={DASHBOARD_TYPES.COMMON_USER}
			isAuthenticated
			currentUser={owner}
			onToggleDashboardView={vi.fn()}
		/>,
	);
});

afterEach(async () => {
	await view.unmount();
	vi.useRealTimers();
});

const search = () => controlLabelled<HTMLInputElement>(view.container, 'Search your articles');

describe('DashboardArticlesPanel search', () => {
	it('starts by listing the owner articles with no search term', () => {
		expect(capturedFilters).toMatchObject({ author_uid: 'owner-uuid', include_facets: false });
		expect(capturedFilters).not.toHaveProperty('q');
	});

	it('searches as the user types, after a 300 ms pause', () => {
		typeInto(search(), 'grammar');

		advance(299);
		expect(capturedFilters).not.toHaveProperty('q');

		advance(1);
		expect(capturedFilters).toMatchObject({ q: 'grammar' });
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

		const form = view.container.querySelector('form') as HTMLFormElement;
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
	});
});

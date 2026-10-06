// @vitest-environment jsdom
import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import { usePendingArticles } from '@/api/articles/moderation';
import { useDashboardCounts } from '@/api/dashboard/useDashboardCounts';
import { useAuth } from '@/hooks/useAuth';
import { choose, controlLabelled } from '@/test/formEvents';
import type { User } from '@/types';
import { renderDashboardRoute } from './dashboardTestRoute';
import Dashboard, { dashboardCountsMeta } from './index';

let articleFilters: Record<string, unknown> | undefined;
let catalogueFilters: Record<string, unknown> | undefined;

vi.mock('@/hooks/useAuth', () => ({ useAuth: vi.fn() }));
vi.mock('@/api/dashboard/useDashboardCounts', () => ({ useDashboardCounts: vi.fn() }));
vi.mock('@/api/articles/hooks/useOwnerProcessingSubscription', () => ({ OwnerProcessingSubscription: () => null }));
vi.mock('@/api/articles/moderation', () => ({ usePendingArticles: vi.fn() }));

const emptyQuery = {
	total: 0,
	isPending: false,
	isError: false,
	hasNextPage: false,
	isFetchingNextPage: false,
	fetchNextPage: vi.fn(),
	refetch: vi.fn(),
};

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: ({ filters }: { filters: Record<string, unknown> }) => {
		articleFilters = filters;
		return { articles: [], ...emptyQuery };
	},
}));

vi.mock('@/api/catalogues/hooks/useInfiniteCatalogues', () => ({
	useInfiniteCatalogues: ({ filters }: { filters: Record<string, unknown> }) => {
		catalogueFilters = filters;
		return { catalogues: [], ...emptyQuery };
	},
}));

const owner = { id: 7, uuid: 'owner-uuid', isAdmin: false } as User;
const admin = { id: 1, uuid: 'admin-uuid', isAdmin: true } as User;

const signIn = (user: User | null, isLoading = false) =>
	vi.mocked(useAuth).mockReturnValue({ isAuthenticated: Boolean(user), isLoading, user } as never);

const navLinks = (container: HTMLElement) =>
	Array.from(container.querySelectorAll('nav[aria-label="Dashboard sections"] a'), (link) => link.textContent);

let route: Awaited<ReturnType<typeof renderDashboardRoute>> | undefined;

beforeEach(() => {
	vi.clearAllMocks();
	articleFilters = undefined;
	catalogueFilters = undefined;
	vi.mocked(useDashboardCounts).mockReturnValue({ data: { articles: 12, lists: 1, awaitingReview: 2 } } as never);
	vi.mocked(usePendingArticles).mockReturnValue({ pendingArticles: [], ...emptyQuery } as never);
});

afterEach(async () => {
	await route?.view.unmount();
	route = undefined;
});

describe('Dashboard route', () => {
	it('renders the dashboard loading family while authentication is restoring', () => {
		signIn(null, true);

		const html = renderToStaticMarkup(
			<MemoryRouter>
				<Dashboard />
			</MemoryRouter>,
		);

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('data-loading-family="dashboard"');
	});

	it('opens Articles with one heading, the counts and the section links', async () => {
		signIn(owner);
		route = await renderDashboardRoute('/dashboard');
		const { container } = route.view;

		expect(container.querySelectorAll('h1')).toHaveLength(1);
		expect(container.textContent).toContain('12 articles · 1 list · 2 awaiting review');
		expect(navLinks(container)).toEqual(['Articles', 'Lists']);
		expect(articleFilters).toMatchObject({ author_uid: 'owner-uuid', per_page: 25 });
		expect(articleFilters).not.toHaveProperty('statuses[]');
	});

	it('shows a new user the empty panel with a way to start', async () => {
		signIn(owner);
		route = await renderDashboardRoute('/dashboard');

		expect(route.view.container.textContent).toContain('You have no articles yet');
		expect(route.view.container.querySelector('a[href="/newarticle"]')).not.toBeNull();
	});

	it('reads the approval filter from the URL and sends it as statuses[]', async () => {
		signIn(owner);
		route = await renderDashboardRoute('/dashboard?status=awaiting');

		expect(articleFilters).toMatchObject({ 'statuses[]': [ARTICLE_STATUS.PENDING, ARTICLE_STATUS.REVIEWING] });
	});

	it('writes a new approval filter to the URL, so back undoes it', async () => {
		signIn(owner);
		route = await renderDashboardRoute('/dashboard');

		choose(controlLabelled(route.view.container, 'Approval'), 'rejected');

		expect(route.search()).toBe('status=rejected');
		expect(articleFilters).toMatchObject({ 'statuses[]': [ARTICLE_STATUS.REJECTED] });

		await route.navigate(-1);

		expect(route.search()).toBe('');
		expect(articleFilters).not.toHaveProperty('statuses[]');
	});

	it('opens Lists from the URL with the built-in lists included', async () => {
		signIn(owner);
		route = await renderDashboardRoute('/dashboard?tab=lists');

		expect(catalogueFilters).toMatchObject({
			owner_uid: 'owner-uuid',
			custom_only: false,
			public_only: false,
			per_page: 25,
		});
		expect(articleFilters).toBeUndefined();
		expect(route.view.container.querySelector('nav a[aria-current="page"]')?.textContent).toBe('Lists');
	});

	it('keeps the review queue for admins only', async () => {
		signIn(owner);
		route = await renderDashboardRoute('/dashboard?tab=review');

		expect(navLinks(route.view.container)).not.toContain('Review queue');
		expect(articleFilters).toMatchObject({ author_uid: 'owner-uuid' });
		expect(usePendingArticles).not.toHaveBeenCalledWith(expect.objectContaining({ enabled: true }));
		await route.view.unmount();

		signIn(admin);
		route = await renderDashboardRoute('/dashboard?tab=review');

		expect(navLinks(route.view.container)).toEqual(['Articles', 'Lists', 'Review queue']);
		expect(usePendingArticles).toHaveBeenCalledWith(expect.objectContaining({ enabled: true }));
		expect(route.view.container.textContent).toContain('Nothing awaits review');
	});
});

describe('dashboardCountsMeta', () => {
	it('joins the three counts, singular where it applies', () => {
		expect(dashboardCountsMeta({ articles: 1, lists: 4, awaitingReview: 0 })).toBe(
			'1 article · 4 lists · 0 awaiting review',
		);
		expect(dashboardCountsMeta({ articles: 1500, lists: 1, awaitingReview: 3 })).toBe(
			'1,500 articles · 1 list · 3 awaiting review',
		);
	});

	it('shows nothing until the counts arrive', () => {
		expect(dashboardCountsMeta(undefined)).toBeUndefined();
	});
});

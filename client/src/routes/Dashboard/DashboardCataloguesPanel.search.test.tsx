// @vitest-environment jsdom
import { act } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuth } from '@/hooks/useAuth';
import { choose, controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import type { User } from '@/types';
import { renderDashboardRoute } from './dashboardTestRoute';

let capturedFilters: Record<string, unknown> | undefined;

vi.mock('@/hooks/useAuth', () => ({ useAuth: vi.fn() }));
vi.mock('@/api/dashboard/useDashboardCounts', () => ({ useDashboardCounts: () => ({ data: undefined }) }));

vi.mock('@/api/catalogues/hooks/useInfiniteCatalogues', () => ({
	useInfiniteCatalogues: ({ filters }: { filters: Record<string, unknown> }) => {
		capturedFilters = filters;

		return {
			catalogues: [],
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

beforeEach(async () => {
	vi.useFakeTimers();
	capturedFilters = undefined;
	vi.mocked(useAuth).mockReturnValue({ isAuthenticated: true, isLoading: false, user: owner } as never);
	route = await renderDashboardRoute('/dashboard?tab=lists');
});

afterEach(async () => {
	await route.view.unmount();
	vi.useRealTimers();
});

describe('Dashboard lists search', () => {
	it('starts with the owner lists, newest first, every type, built-in lists included', () => {
		expect(capturedFilters).toMatchObject({
			owner_uid: 'owner-uuid',
			search: undefined,
			sort_by: 'created_at',
			sort_dir: 'desc',
			type: undefined,
			public_only: false,
			custom_only: false,
		});
	});

	it('applies type and sort at once, and the keyword after a 300 ms pause', () => {
		choose(controlLabelled(route.view.container, 'Catalogue type'), '7');
		choose(controlLabelled(route.view.container, 'Sort by'), 'pop');

		expect(capturedFilters).toMatchObject({ type: 7, sort_by: 'views', sort_dir: 'desc' });
		expect(route.search()).toBe('tab=lists&type=7&sort=pop');

		typeInto(controlLabelled(route.view.container, 'Search catalogues'), '  tokyo  ');
		advance(299);
		expect(capturedFilters).toMatchObject({ search: undefined });

		advance(1);
		expect(capturedFilters).toMatchObject({ search: 'tokyo', type: 7, sort_by: 'views' });
	});

	it('applies the keyword at once on Enter', () => {
		typeInto(controlLabelled(route.view.container, 'Search catalogues'), 'tokyo');
		submitForm(route.view.container.querySelector('form[role="search"]') as HTMLFormElement);

		expect(capturedFilters).toMatchObject({ search: 'tokyo' });
	});

	it('restores the filters from the URL', async () => {
		await route.navigate('/dashboard?tab=lists&q=verbs&type=7');

		expect(capturedFilters).toMatchObject({ search: 'verbs', type: 7 });
		expect(controlLabelled<HTMLInputElement>(route.view.container, 'Search catalogues').value).toBe('verbs');
	});
});

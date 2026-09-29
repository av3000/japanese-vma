// @vitest-environment jsdom
import { act } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { choose, controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import type { User } from '@/types';
import DashboardCataloguesPanel from './DashboardCataloguesPanel';

let capturedFilters: Record<string, unknown> | undefined;

vi.mock('@/api/catalogues/hooks/useInfiniteCatalogues', () => ({
	useInfiniteCatalogues: ({ filters }: { filters: Record<string, unknown> }) => {
		capturedFilters = filters;

		return {
			catalogues: [],
			total: 0,
			error: null,
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			isPending: false,
			isError: false,
		};
	},
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

const owner = { id: 7, uuid: 'owner-uuid' } as User;

let view: Awaited<ReturnType<typeof renderWithAct>>;

const advance = (ms: number) =>
	act(() => {
		vi.advanceTimersByTime(ms);
	});

beforeEach(async () => {
	vi.useFakeTimers();
	capturedFilters = undefined;
	view = await renderWithAct(
		<MemoryRouter>
			<DashboardCataloguesPanel isAuthenticated currentUser={owner} />
		</MemoryRouter>,
	);
});

afterEach(async () => {
	await view.unmount();
	vi.useRealTimers();
});

describe('DashboardCataloguesPanel search', () => {
	it('starts with the same request as before: the owner lists, newest first, every type', () => {
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

	it('maps a keyword, a type and a sort onto the same request params as before, after a 300 ms pause', () => {
		typeInto(controlLabelled(view.container, 'Search catalogues'), '  tokyo  ');
		choose(controlLabelled(view.container, 'Catalogue type'), '7');
		choose(controlLabelled(view.container, 'Sort by'), 'pop');

		advance(299);
		expect(capturedFilters).toMatchObject({ search: undefined, sort_by: 'created_at', type: undefined });

		advance(1);
		expect(capturedFilters).toMatchObject({ search: 'tokyo', sort_by: 'views', sort_dir: 'desc', type: 7 });
	});

	it('applies the form at once on Enter', () => {
		typeInto(controlLabelled(view.container, 'Search catalogues'), 'tokyo');
		submitForm(view.container.querySelector('form') as HTMLFormElement);

		expect(capturedFilters).toMatchObject({ search: 'tokyo' });
	});
});

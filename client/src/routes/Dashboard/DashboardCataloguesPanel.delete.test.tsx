// @vitest-environment jsdom
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { makeDashboardCatalogue } from '@/components/features/dashboard/dashboardListFixtures';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import type { User } from '@/types';
import DashboardCataloguesPanel from './DashboardCataloguesPanel';
import { defaultDashboardViewState } from './dashboardSearchParams';

const catalogue = makeDashboardCatalogue({ uuid: 'doomed', title: 'Verbs to drop' });
let outcome: 'success' | 'error' = 'success';
const mutate = vi.fn((_uuid: string, callbacks: { onSuccess: () => void; onError: () => void }) =>
	outcome === 'success' ? callbacks.onSuccess() : callbacks.onError(),
);

vi.mock('@/api/catalogues/hooks/useDeleteCatalogueMutation', () => ({
	useDeleteCatalogueMutation: () => ({ mutate, isPending: false }),
}));
vi.mock('@/api/catalogues/hooks/useInfiniteCatalogues', () => ({
	useInfiniteCatalogues: () => ({
		catalogues: [catalogue],
		total: 1,
		isPending: false,
		isError: false,
		hasNextPage: false,
		isFetchingNextPage: false,
		fetchNextPage: vi.fn(),
		refetch: vi.fn(),
	}),
}));
// The native <dialog> is not what is under test: render the confirmation inline once opened.
vi.mock('@/components/features/DeleteInstanceModal', () => ({
	DeleteInstanceModal: ({
		controller,
		instanceName,
		onDelete,
	}: {
		controller: { isRendered: boolean };
		instanceName: string;
		onDelete: () => void;
	}): ReactNode =>
		controller.isRendered ? (
			<div data-testid="confirm">
				<p>{instanceName}</p>
				<button type="button" onClick={onDelete}>
					Confirm delete
				</button>
			</div>
		) : null,
}));

const owner = { id: 7, uuid: 'owner-uuid', isAdmin: false } as User;

const renderPanel = () =>
	renderWithAct(
		<MemoryRouter>
			<DashboardCataloguesPanel user={owner} view={defaultDashboardViewState('lists')} onViewChange={vi.fn()} />
		</MemoryRouter>,
	);

describe('DashboardCataloguesPanel delete', () => {
	beforeEach(() => {
		mutate.mockClear();
		outcome = 'success';
	});

	it('names the list, deletes it on confirm and moves focus to the count line', async () => {
		const view = await renderPanel();

		await view.flush(() =>
			requireElement<HTMLButtonElement>(view.container, 'button[aria-label="Delete Verbs to drop"]').click(),
		);
		expect(requireElement(view.container, '[data-testid="confirm"]').textContent).toContain('Verbs to drop');

		await view.flush(() =>
			requireElement<HTMLButtonElement>(view.container, '[data-testid="confirm"] button').click(),
		);

		expect(mutate).toHaveBeenCalledWith('doomed', expect.any(Object));
		expect(document.activeElement?.textContent).toBe('Showing 1 of 1 lists');
		await view.unmount();
	});

	it('says so in plain words when the delete fails', async () => {
		outcome = 'error';
		const view = await renderPanel();

		await view.flush(() =>
			requireElement<HTMLButtonElement>(view.container, 'button[aria-label="Delete Verbs to drop"]').click(),
		);
		await view.flush(() =>
			requireElement<HTMLButtonElement>(view.container, '[data-testid="confirm"] button').click(),
		);

		expect(view.container.textContent).toContain('The list could not be deleted. Try again in a moment.');
		await view.unmount();
	});
});

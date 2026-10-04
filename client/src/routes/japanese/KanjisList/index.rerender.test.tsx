// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { KanjiListResource } from '@/api/generated/model/kanjiListResource';
import { kanjiRows, repeatRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { click } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import KanjisList from './index';

/*
 * The Save click path end to end on the real route: widget -> route handler -> query cache -> list
 * re-render. At 100 rows (four "Load more" pages) only the saved row may render again
 * (V1-FINDINGS-02, #488).
 */

const items = repeatRows(kanjiRows, 100);
const widgetRenders = new Map<number, number>();

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: true }) }));

vi.mock('@/api/generated/kanji/kanji', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/kanji/kanji')>('@/api/generated/kanji/kanji');
	// `items` is read when the request runs, after this module has finished evaluating.
	const kanjiIndex = vi.fn(
		async (): Promise<KanjiListResource> => ({
			items,
			pagination: { page: 1, per_page: 100, total: 100, last_page: 1, has_more: false },
		}),
	);

	return { ...actual, kanjiIndex };
});

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: {
		entityId: number;
		onStateChange?: (state: { isBookmarked: boolean; isKnown: boolean }) => void;
	}) => {
		widgetRenders.set(props.entityId, (widgetRenders.get(props.entityId) ?? 0) + 1);

		return (
			<button
				type="button"
				data-save={props.entityId}
				onClick={() => props.onStateChange?.({ isBookmarked: true, isKnown: false })}
			>
				save
			</button>
		);
	},
}));

describe('KanjisList Save click', () => {
	beforeEach(() => widgetRenders.clear());

	it('re-renders only the saved row out of 100', async () => {
		const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
		const view = await renderWithAct(
			<QueryClientProvider client={queryClient}>
				<MemoryRouter>
					<KanjisList />
				</MemoryRouter>
			</QueryClientProvider>,
		);
		const target = items[41];

		// The mocked request resolves on a later tick; wait for the 100 rows to render.
		for (let attempt = 0; attempt < 20 && !view.container.querySelector('[data-save]'); attempt += 1) {
			await view.flush(() => new Promise<void>((resolve) => setTimeout(resolve, 10)));
		}

		expect(view.container.querySelectorAll('[data-save]')).toHaveLength(100);

		widgetRenders.clear();
		click(requireElement(view.container, `[data-save="${target.id}"]`));
		// React Query hands the cache change to the observers on a later tick.
		await view.flush(() => new Promise<void>((resolve) => setTimeout(resolve, 10)));

		expect([...widgetRenders.entries()]).toEqual([[target.id, 1]]);
		await view.unmount();
	});
});

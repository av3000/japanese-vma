// @vitest-environment jsdom
import { StrictMode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
	addOrRemoveCatalogueForItem,
	fetchCataloguesForItem,
	type CatalogueForItem,
} from '@/api/catalogues/cataloguesForItem';
import { SavedListType } from '@/shared/constants/enums';
import { click } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { AuthorizedBookmarkWidget } from './';

/*
 * The widget keeps one piece of state, the viewer's lists for the item, and derives the saved and
 * known flags from it (V1-FINDINGS-03, #489). The tests run under StrictMode, which replays
 * effects and double-invokes state updaters: `onStateChange` must still fire once per change.
 */

vi.mock('@/api/catalogues/cataloguesForItem', async () => {
	const actual = await vi.importActual<typeof import('@/api/catalogues/cataloguesForItem')>(
		'@/api/catalogues/cataloguesForItem',
	);

	return { ...actual, fetchCataloguesForItem: vi.fn(), addOrRemoveCatalogueForItem: vi.fn() };
});

vi.mock('@/hooks/useModal', () => ({
	useModal: (_ref: unknown, { id }: { id: string }) => ({
		id,
		dialogRef: { current: null },
		isOpen: false,
		isRendered: true,
		open: () => undefined,
		close: () => undefined,
	}),
}));

// The dialog itself is covered by its own tests; here it is a button per list.
vi.mock('@/components/features/catalogues/CatalogueBookmarkModal', () => ({
	CatalogueBookmarkModal: ({
		lists,
		onListAction,
	}: {
		lists: CatalogueForItem[];
		onListAction: (list: CatalogueForItem, action: 'add' | 'remove') => void;
	}) => (
		<ul>
			{lists.map((list) => (
				<li key={list.id}>
					<button
						type="button"
						data-list={list.id}
						onClick={() => onListAction(list, list.contains_item ? 'remove' : 'add')}
					>
						{list.title}
					</button>
				</li>
			))}
		</ul>
	),
}));

const fetchMock = vi.mocked(fetchCataloguesForItem);
const mutateMock = vi.mocked(addOrRemoveCatalogueForItem);

const catalogue = (id: number, type: SavedListType, containsItem = false) =>
	({
		id,
		uuid: `catalogue-${id}`,
		title: `List ${id}`,
		type,
		type_label: 'Kanji',
		publicity: 0,
		contains_item: containsItem,
	}) satisfies CatalogueForItem;

const SAVED = catalogue(1, SavedListType.KANJIS);
const KNOWN = catalogue(2, SavedListType.KNOWNKANJIS);

const widget = (props: Partial<React.ComponentProps<typeof AuthorizedBookmarkWidget>> = {}) => (
	<StrictMode>
		<AuthorizedBookmarkWidget
			entityId={42}
			instanceObjectType={SavedListType.KANJIS}
			isKnownType={SavedListType.KNOWNKANJIS}
			loadOnMount={false}
			itemLabel="水"
			compact
			{...props}
		/>
	</StrictMode>
);

const later = () => new Promise<void>((resolve) => setTimeout(resolve, 10));
const label = (root: HTMLElement) => requireElement(root, 'button[aria-controls]').getAttribute('aria-label');

describe('AuthorizedBookmarkWidget state', () => {
	beforeEach(() => {
		fetchMock.mockReset();
		mutateMock.mockReset();
		mutateMock.mockResolvedValue(undefined);
	});

	it('shows the initial props until the lists have loaded, with no effect copying them', async () => {
		const view = await renderWithAct(widget({ initialIsBookmarked: true }));

		expect(label(view.container)).toBe('Saved: 水');

		await view.rerender(widget({ initialIsBookmarked: false }));

		expect(label(view.container)).toBe('Save 水');
		await view.unmount();
	});

	it('reports each change once under StrictMode, and derives the flags from the lists', async () => {
		fetchMock.mockResolvedValue([SAVED, KNOWN]);
		const onStateChange = vi.fn();
		const view = await renderWithAct(widget({ onStateChange }));

		await view.flush(() => click(requireElement(view.container, 'button[aria-controls]')));
		await view.flush(later);

		// Opening the dialog loads the lists once and reports the state they imply.
		expect(fetchMock).toHaveBeenCalledTimes(1);
		expect(onStateChange.mock.calls).toEqual([[{ isBookmarked: false, isKnown: false }]]);

		await view.flush(() => click(requireElement(view.container, '[data-list="1"]')));
		await view.flush(later);

		expect(mutateMock).toHaveBeenCalledTimes(1);
		expect(onStateChange.mock.calls).toEqual([
			[{ isBookmarked: false, isKnown: false }],
			[{ isBookmarked: true, isKnown: false }],
		]);
		expect(label(view.container)).toBe('Saved: 水');

		await view.flush(() => click(requireElement(view.container, '[data-list="2"]')));
		await view.flush(later);

		expect(onStateChange).toHaveBeenCalledTimes(3);
		expect(onStateChange).toHaveBeenLastCalledWith({ isBookmarked: true, isKnown: true });
		expect(view.container.textContent).toContain('Known');
		await view.unmount();
	});

	it('removes a saved item the same way', async () => {
		fetchMock.mockResolvedValue([catalogue(1, SavedListType.KANJIS, true), KNOWN]);
		const onStateChange = vi.fn();
		const view = await renderWithAct(widget({ onStateChange, initialIsBookmarked: true }));

		await view.flush(() => click(requireElement(view.container, 'button[aria-controls]')));
		await view.flush(later);
		await view.flush(() => click(requireElement(view.container, '[data-list="1"]')));
		await view.flush(later);

		expect(onStateChange).toHaveBeenCalledTimes(2);
		expect(onStateChange).toHaveBeenLastCalledWith({ isBookmarked: false, isKnown: false });
		expect(label(view.container)).toBe('Save 水');
		await view.unmount();
	});

	it('loads on mount once and reports once under StrictMode', async () => {
		fetchMock.mockResolvedValue([catalogue(1, SavedListType.KANJIS, true)]);
		const onStateChange = vi.fn();
		const view = await renderWithAct(widget({ loadOnMount: true, onStateChange }));
		await view.flush(later);

		expect(onStateChange.mock.calls).toEqual([[{ isBookmarked: true, isKnown: false }]]);
		expect(label(view.container)).toBe('Saved: 水');
		await view.unmount();
	});

	it('does not reload when the parent passes a new inline onStateChange', async () => {
		fetchMock.mockResolvedValue([]);
		const view = await renderWithAct(
			<AuthorizedBookmarkWidget
				entityId={42}
				instanceObjectType={SavedListType.KANJIS}
				onStateChange={() => undefined}
			/>,
		);
		await view.flush(later);

		await view.rerender(
			<AuthorizedBookmarkWidget
				entityId={42}
				instanceObjectType={SavedListType.KANJIS}
				onStateChange={() => undefined}
			/>,
		);
		await view.flush(later);

		expect(fetchMock).toHaveBeenCalledTimes(1);
		await view.unmount();
	});

	it('keeps the lists as the source once loaded, whatever the props say afterwards', async () => {
		fetchMock.mockResolvedValue([catalogue(1, SavedListType.KANJIS, true)]);
		const view = await renderWithAct(widget({ initialIsBookmarked: false }));

		await view.flush(() => click(requireElement(view.container, 'button[aria-controls]')));
		await view.flush(later);
		await view.rerender(widget({ initialIsBookmarked: false }));

		expect(label(view.container)).toBe('Saved: 水');
		await view.unmount();
	});
});

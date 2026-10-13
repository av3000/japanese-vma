import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import {
	getCatalogueAddItemMockHandler,
	getCatalogueForItemMockHandler,
	getCatalogueRemoveItemMockHandler,
} from '@/api/generated/catalogue/catalogue.msw';
import type { StoreCatalogueItemRequest } from '@/api/generated/model/storeCatalogueItemRequest';
import { SavedListType } from '@/shared/constants/enums';
import { users } from '@/storybook/fixtures/users';
import { catalogueForItem, someSavedLists } from '../fixtures';
import { AuthorizedBookmarkWidget } from './';

const ITEM_ID = 321;

/*
 * The widget on its real client: it loads the viewer's catalogues for the item on mount, then
 * adds or removes through the generated item endpoints. Every request goes through the
 * Orval-generated MSW handlers (#333), overridden with the generated factories.
 */

/** What the generated POST and DELETE handlers received, newest last; cleared before each story. */
const added: { uuid: string; body: StoreCatalogueItemRequest }[] = [];
const removed: { uuid: string; itemId: string }[] = [];

const listsHolding = (listId: number) => someSavedLists.map((list) => ({ ...list, contains_item: list.id === listId }));

const meta = {
	title: 'Features/Catalogues/AuthorizedBookmarkWidget',
	component: AuthorizedBookmarkWidget,
	// The pages render the widget for signed-in viewers only; it has no guest state of its own.
	parameters: { layout: 'centered', auth: { user: users.member } },
	args: {
		entityId: ITEM_ID,
		instanceObjectType: SavedListType.ARTICLES,
		modalTitle: 'Choose Articles List to add',
		label: 'Save to a catalogue',
		savedLabel: 'Saved to a catalogue',
		onStateChange: fn(),
	},
	decorators: [
		(Story) => (
			<div style={{ width: 280 }}>
				<Story />
			</div>
		),
	],
	beforeEach: ({ msw }) => {
		added.length = 0;
		removed.length = 0;

		msw.use(
			getCatalogueForItemMockHandler({ items: listsHolding(0) }),
			getCatalogueAddItemMockHandler(async ({ request, params }) => {
				added.push({ uuid: String(params.uuid), body: (await request.json()) as StoreCatalogueItemRequest });

				return [];
			}),
			getCatalogueRemoveItemMockHandler(({ params }) => {
				removed.push({ uuid: String(params.uuid), itemId: String(params.itemId) });

				return 204;
			}),
		);
	},
} satisfies Meta<typeof AuthorizedBookmarkWidget>;

export default meta;

type Story = StoryObj<typeof meta>;

const openModal = async (canvasElement: HTMLElement, buttonName: string) => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: buttonName }));

	const dialog = within(await within(document.body).findByRole('dialog', { name: 'Choose Articles List to add' }));
	// The panel fades in; wait until it is fully there before reading or clicking it.
	await waitFor(() => expect(dialog.getByRole('link', { name: '+ Create a new list' })).toBeVisible());

	return dialog;
};

/** The item is in none of the viewer's catalogues. */
export const NotSaved: Story = {
	play: async ({ canvasElement, args }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Save to a catalogue' })).toHaveAttribute(
			'aria-expanded',
			'false',
		);
		await waitFor(() => expect(args.onStateChange).toHaveBeenCalledWith({ isBookmarked: false, isKnown: false }));
	},
};

/** The item is in one of them: the loaded lists turn the button to its saved label. */
export const Saved: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueForItemMockHandler({ items: listsHolding(2) }));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('button', { name: 'Saved to a catalogue' })).toBeVisible();
	},
};

/** The page already knows the item is saved; the button says so before the viewer's lists arrive. */
export const SavedBeforeTheListsLoad: Story = {
	args: { initialIsBookmarked: true },
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueForItemMockHandler(async () => {
				await delay('infinite');

				return { items: [] };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Saved to a catalogue' })).toBeVisible();
	},
};

/** Open the modal and add the item to a list: the POST carries the item id and the row turns to Remove. */
export const ModalOpen: Story = {
	play: async ({ canvasElement, args }) => {
		const dialog = await openModal(canvasElement, 'Save to a catalogue');
		await expect(await dialog.findByRole('link', { name: 'Reading list for N3' })).toBeVisible();
		await expect(dialog.getAllByRole('button', { name: 'Add' })).toHaveLength(3);

		const row = within(dialog.getByRole('link', { name: 'Station announcements' }).closest('li') as HTMLElement);
		await userEvent.click(row.getByRole('button', { name: 'Add' }));

		await waitFor(() => expect(row.getByRole('button', { name: 'Remove' })).toBeEnabled());
		await expect(added).toEqual([{ uuid: someSavedLists[1].uuid, body: { item_id: ITEM_ID } }]);
		await expect(args.onStateChange).toHaveBeenLastCalledWith({ isBookmarked: true, isKnown: false });
	},
};

/** From a saved item, Remove calls the DELETE endpoint with the list and the item. */
export const RemovesFromAList: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueForItemMockHandler({ items: listsHolding(2) }));
	},
	play: async ({ canvasElement, args }) => {
		// The saved label appears once the viewer's lists have loaded.
		await within(canvasElement).findByRole('button', { name: 'Saved to a catalogue' });
		const dialog = await openModal(canvasElement, 'Saved to a catalogue');
		await userEvent.click(await dialog.findByRole('button', { name: 'Remove' }));

		await waitFor(() => expect(dialog.queryByRole('button', { name: 'Remove' })).toBeNull());
		await expect(removed).toEqual([{ uuid: someSavedLists[1].uuid, itemId: String(ITEM_ID) }]);
		await expect(args.onStateChange).toHaveBeenLastCalledWith({ isBookmarked: false, isKnown: false });
	},
};

/** In a table row: an icon button named for the item, no visible text. */
export const IconButton: Story = {
	args: {
		label: undefined,
		savedLabel: undefined,
		itemLabel: '駅',
		instanceObjectType: SavedListType.KANJIS,
	},
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueForItemMockHandler({
				items: [
					catalogueForItem(1, { title: 'Station kanji', type: SavedListType.KANJIS, contains_item: true }),
				],
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('button', { name: 'Saved: 駅' })).toBeVisible();
	},
};

/** The viewer's lists do not load. The button still works; the modal has nothing to offer but a new list. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueForItemMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const dialog = await openModal(canvasElement, 'Save to a catalogue');
		await expect(dialog.getByText('You have no lists created.')).toBeVisible();
	},
};

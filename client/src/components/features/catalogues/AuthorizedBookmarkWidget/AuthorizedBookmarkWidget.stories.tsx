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

/**
 * The trigger names the modal it opens (`aria-controls`) and says whether it is open
 * (`aria-expanded`), labelled or not.
 */
const expectClosedTrigger = async (button: HTMLElement) => {
	await expect(button).toHaveAttribute('aria-expanded', 'false');
	await expect(button.getAttribute('aria-controls')).toMatch(/\S/);
};

/** The labelled trigger opens its modal: `aria-expanded` turns true and `aria-controls` names the dialog. */
export const LabelledTriggerControlsTheModal: Story = {
	play: async ({ canvasElement }) => {
		const button = within(canvasElement).getByRole('button', { name: 'Save to a catalogue' });
		await expectClosedTrigger(button);

		const dialog = await openModal(canvasElement, 'Save to a catalogue');

		await expect(button).toHaveAttribute('aria-expanded', 'true');
		await expect(dialog.getByRole('link', { name: '+ Create a new list' }).closest('dialog')).toHaveAttribute(
			'id',
			button.getAttribute('aria-controls'),
		);
	},
};

/** The saved labelled trigger keeps the same contract. */
export const LabelledTriggerSaved: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueForItemMockHandler({ items: listsHolding(2) }));
	},
	play: async ({ canvasElement }) => {
		await expectClosedTrigger(await within(canvasElement).findByRole('button', { name: 'Saved to a catalogue' }));
	},
};

const ICON_ONLY = {
	label: undefined,
	savedLabel: undefined,
	itemLabel: '水',
	instanceObjectType: SavedListType.KANJIS,
};

/** In a table row, not saved: an icon button named "Save 水", no visible text. */
export const IconButtonNotSaved: Story = {
	args: ICON_ONLY,
	play: async ({ canvasElement, args }) => {
		await waitFor(() => expect(args.onStateChange).toHaveBeenCalledWith({ isBookmarked: false, isKnown: false }));

		const button = within(canvasElement).getByRole('button', { name: 'Save 水' });
		await expectClosedTrigger(button);
		await expect(button).not.toHaveTextContent('Save');
	},
};

/** The labelled trigger in a 360px-wide rail, on a phone: nothing scrolls sideways. */
export const LabelledOnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(window.innerWidth).toBe(360);
		await expect(within(canvasElement).getByRole('button', { name: 'Save to a catalogue' })).toBeVisible();
		await expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(window.innerWidth);
	},
};

/** Both triggers on a 1400px desktop: nothing scrolls sideways. */
export const BothOnWideDesktop: Story = {
	parameters: {
		viewport: {
			options: { wide: { name: 'wide (1400px)', styles: { width: '1400px', height: '900px' }, type: 'desktop' } },
		},
	},
	globals: { viewport: { value: 'wide' } },
	render: (args) => (
		<>
			<AuthorizedBookmarkWidget {...args} />
			<AuthorizedBookmarkWidget {...args} {...ICON_ONLY} entityId={ITEM_ID + 1} />
		</>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(window.innerWidth).toBe(1400);
		await expect(canvas.getByRole('button', { name: 'Save to a catalogue' })).toBeVisible();
		await expect(await canvas.findByRole('button', { name: 'Save 水' })).toBeVisible();
		await expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(window.innerWidth);
	},
};

/** In a table row, saved: an icon button named "Saved: 駅". */
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
		const button = await within(canvasElement).findByRole('button', { name: 'Saved: 駅' });

		await expect(button).toBeVisible();
		await expectClosedTrigger(button);
	},
};

/**
 * Table rows pass `loadOnMount={false}`: the lists load when the modal opens. Until they arrive the
 * modal says so, never "You have no lists created." to a member who has lists.
 */
export const LoadingInModal: Story = {
	args: { loadOnMount: false },
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueForItemMockHandler(async () => {
				await delay('infinite');

				return { items: [] };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const dialog = await openModal(canvasElement, 'Save to a catalogue');
		await expect(dialog.getByRole('status')).toHaveTextContent('Loading your lists…');
		await expect(dialog.queryByText('You have no lists created.')).toBeNull();
	},
};

/** The viewer's lists do not load. The modal says so instead of claiming there are no lists. */
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
		// Opening retries the failed mount load, which fails again.
		await expect(await dialog.findByRole('alert')).toHaveTextContent('Your lists could not be loaded.');
		await expect(dialog.queryByText('You have no lists created.')).toBeNull();
	},
};

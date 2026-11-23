import { useRef, type ComponentProps } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { manyLists, someSavedLists } from '../fixtures';
import { CatalogueBookmarkModal } from './';

type ModalProps = ComponentProps<typeof CatalogueBookmarkModal>;

/** Owns the dialog the way a parent does: its ref and open state come from `useModal`. */
const WithTrigger = (props: Omit<ModalProps, 'controller'>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef);

	return (
		<>
			<Button variant="outline" onClick={controller.open}>
				Save to a catalogue
			</Button>
			<CatalogueBookmarkModal {...props} controller={controller} />
		</>
	);
};

const openDialog = async (canvasElement: HTMLElement, name = 'Save to List') => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: 'Save to a catalogue' }));

	const dialog = within(await within(document.body).findByRole('dialog', { name }));
	// The panel fades in; wait until it is fully there before reading or clicking it.
	await waitFor(() => expect(dialog.getByRole('link', { name: '+ Create a new list' })).toBeVisible());

	return dialog;
};

const meta = {
	title: 'Features/Catalogues/CatalogueBookmarkModal',
	component: CatalogueBookmarkModal,
	parameters: { layout: 'centered' },
	// The render below owns the controller; this placeholder only satisfies the props type.
	args: {
		controller: {
			id: 'storybook-catalogue-bookmark',
			dialogRef: { current: null },
			isOpen: false,
			isRendered: false,
			open: fn(),
			close: fn(),
		},
		lists: someSavedLists,
		loadingListIds: [],
		onListAction: fn(),
	},
	argTypes: { controller: { control: false }, onListAction: { control: false } },
	render: ({ controller: _controller, ...args }) => <WithTrigger {...args} />,
} satisfies Meta<typeof CatalogueBookmarkModal>;

export default meta;

type Story = StoryObj<typeof meta>;

/** No catalogue yet: the empty text and the way to make one. */
export const NoLists: Story = {
	args: { lists: [] },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByText('You have no lists created.')).toBeVisible();
		await expect(dialog.getByRole('link', { name: '+ Create a new list' })).toHaveAttribute(
			'href',
			'/catalogues/new',
		);
		await expect(dialog.queryByRole('button', { name: 'Add' })).toBeNull();
	},
};

/** A catalogue that holds the item offers Remove, the others Add; each reports its list and the action. */
export const SomeSaved: Story = {
	play: async ({ canvasElement, args }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByRole('link', { name: 'Station announcements' })).toHaveAttribute(
			'href',
			`/catalogues/${someSavedLists[1].uuid}`,
		);
		await expect(dialog.getAllByRole('button', { name: 'Add' })).toHaveLength(2);

		await userEvent.click(dialog.getByRole('button', { name: 'Remove' }));
		await expect(args.onListAction).toHaveBeenLastCalledWith(someSavedLists[1], 'remove');

		await userEvent.click(dialog.getAllByRole('button', { name: 'Add' })[0]);
		await expect(args.onListAction).toHaveBeenLastCalledWith(someSavedLists[0], 'add');
	},
};

/** Thirty catalogues, one with a very long title: the list scrolls inside the dialog. */
export const ManyLists: Story = {
	args: { lists: manyLists },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getAllByRole('listitem')).toHaveLength(manyLists.length);
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};

/** A write is in flight for one list: only its button is disabled. */
export const Saving: Story = {
	args: { loadingListIds: [someSavedLists[0].id] },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		const rows = dialog.getAllByRole('listitem');
		await expect(within(rows[0]).getByRole('button')).toBeDisabled();
		await expect(within(rows[1]).getByRole('button', { name: 'Remove' })).toBeEnabled();
		await expect(within(rows[2]).getByRole('button', { name: 'Add' })).toBeEnabled();
	},
};

/** The caller's own title, empty text and create link. */
export const CustomCopy: Story = {
	args: {
		lists: [],
		title: 'Choose Kanji List to add',
		ariaLabel: 'Choose Kanji List to add',
		emptyText: 'You have no kanji lists yet.',
		createListHref: '/catalogues/new?type=6',
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Choose Kanji List to add');
		await expect(dialog.getByRole('heading', { name: 'Choose Kanji List to add' })).toBeVisible();
		await expect(dialog.getByText('You have no kanji lists yet.')).toBeVisible();
		await expect(dialog.getByRole('link', { name: '+ Create a new list' })).toHaveAttribute(
			'href',
			'/catalogues/new?type=6',
		);
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getAllByRole('listitem')).toHaveLength(someSavedLists.length);
	},
};

import { useRef, type ComponentProps } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { DeleteInstanceModal } from './';

type ModalProps = ComponentProps<typeof DeleteInstanceModal>;

/** Owns the dialog the way a parent does: its ref and open state come from `useModal`. */
const WithTrigger = (props: Omit<ModalProps, 'controller'>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef);

	return (
		<>
			<Button variant="danger" onClick={controller.open}>
				Delete
			</Button>
			<DeleteInstanceModal {...props} controller={controller} />
		</>
	);
};

const openDialog = async (canvasElement: HTMLElement, name: string) => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: 'Delete' }));

	const dialog = within(await within(document.body).findByRole('dialog', { name }));
	// The panel fades in; wait until it is fully there before reading or clicking it.
	await waitFor(() => expect(dialog.getByRole('heading')).toBeVisible());

	return dialog;
};

const meta = {
	title: 'Features/Articles/DeleteInstanceModal',
	component: DeleteInstanceModal,
	parameters: { layout: 'centered' },
	// The render below owns the controller; this placeholder only satisfies the props type.
	args: {
		controller: {
			id: 'storybook-delete-instance',
			dialogRef: { current: null },
			isOpen: false,
			isRendered: false,
			open: fn(),
			close: fn(),
		},
		instanceName: 'Placeholder',
		onDelete: fn(),
		isProcessing: false,
	},
	argTypes: { controller: { control: false }, onDelete: { control: false } },
	render: ({ controller: _controller, ...args }) => <WithTrigger {...args} />,
} satisfies Meta<typeof DeleteInstanceModal>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Article details: the component's own copy, with the Japanese title in the sentence. */
export const Article: Story = {
	args: { instanceName: '新宿駅の新しい改札が完成' },
	play: async ({ canvasElement, args }) => {
		const dialog = await openDialog(canvasElement, 'Delete instance');
		await expect(dialog.getByRole('heading', { name: 'Are you absolutely sure?' })).toBeVisible();
		await expect(dialog.getByText('新宿駅の新しい改札が完成')).toBeVisible();

		await userEvent.click(dialog.getByRole('button', { name: 'Yes, Delete Instance' }));
		await expect(args.onDelete).toHaveBeenCalledTimes(1);
	},
};

export const Catalogue: Story = {
	args: {
		instanceName: 'Trains and stations vocabulary',
		deleteLabel: 'Yes, Delete Catalogue',
		ariaLabel: 'Delete catalogue',
	},
	play: async ({ canvasElement, args }) => {
		const dialog = await openDialog(canvasElement, 'Delete catalogue');
		await userEvent.click(dialog.getByRole('button', { name: 'Yes, Delete Catalogue' }));
		await expect(args.onDelete).toHaveBeenCalledTimes(1);
	},
};

/** The dashboard's article and catalogue lists ask in their own words. */
export const DashboardArticle: Story = {
	args: {
		instanceName: '新宿駅の新しい改札が完成',
		title: 'Delete this article?',
		deleteLabel: 'Delete article',
		ariaLabel: 'Delete article',
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Delete article');
		await expect(dialog.getByRole('heading', { name: 'Delete this article?' })).toBeVisible();
		await expect(dialog.getByRole('button', { name: 'Delete article' })).toBeEnabled();
	},
};

export const DashboardList: Story = {
	args: {
		instanceName: 'Trains and stations vocabulary',
		title: 'Delete this list?',
		deleteLabel: 'Delete list',
		ariaLabel: 'Delete list',
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Delete list');
		await expect(dialog.getByRole('heading', { name: 'Delete this list?' })).toBeVisible();
	},
};

export const Sentence: Story = {
	args: {
		instanceName: '駅はどこですか。',
		deleteLabel: 'Yes, delete the sentence',
		ariaLabel: 'Delete sentence',
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Delete sentence');
		await expect(dialog.getByRole('button', { name: 'Yes, delete the sentence' })).toBeVisible();
	},
};

export const Post: Story = {
	args: {
		instanceName: 'How do you remember the difference between 行く and 来る?',
		deleteLabel: 'Yes, Delete Post',
		ariaLabel: 'Delete post',
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Delete post');
		await expect(dialog.getByRole('button', { name: 'Yes, Delete Post' })).toBeVisible();
	},
};

/** The request is in flight: the confirm control reads "Deleting..." and is disabled, so it cannot be sent twice. */
export const Deleting: Story = {
	args: { instanceName: '新宿駅の新しい改札が完成', isProcessing: true },
	play: async ({ canvasElement, args }) => {
		const dialog = await openDialog(canvasElement, 'Delete instance');
		await expect(dialog.getByRole('button', { name: 'Deleting...' })).toBeDisabled();
		await expect(dialog.getByRole('button', { name: 'Cancel' })).toBeEnabled();
		await expect(args.onDelete).not.toHaveBeenCalled();
	},
};

/** Cancel closes the dialog without deleting and puts focus back on the control that opened it. */
export const Cancel: Story = {
	args: { instanceName: '新宿駅の新しい改札が完成' },
	play: async ({ canvasElement, args }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Delete' });
		const dialog = await openDialog(canvasElement, 'Delete instance');

		await userEvent.click(dialog.getByRole('button', { name: 'Cancel' }));
		await waitFor(() => expect(within(document.body).queryByRole('dialog')).not.toBeInTheDocument());
		await waitFor(() => expect(trigger).toHaveFocus());
		await expect(args.onDelete).not.toHaveBeenCalled();
	},
};

/** A very long name wraps inside the dialog; nothing scrolls sideways. */
export const LongName: Story = {
	args: { instanceName: `${'とても長い記事のタイトル'.repeat(20)} ${'a-very-long-title '.repeat(12)}` },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Delete instance');
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
		await expect(dialog.getByRole('button', { name: 'Yes, Delete Instance' })).toBeVisible();
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { instanceName: '新宿駅の新しい改札が完成' },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement, 'Delete instance');
		await expect(dialog.getByRole('button', { name: 'Yes, Delete Instance' })).toBeVisible();
	},
};

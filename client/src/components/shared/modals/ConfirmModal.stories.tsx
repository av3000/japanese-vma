import type { ComponentProps } from 'react';
import { useRef } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { ConfirmModal } from './ConfirmModal';

const meta = {
	component: ConfirmModal,
	title: 'Shared/Modals/ConfirmModal',
	parameters: {
		docs: {
			description: {
				component: `Standard confirm modal built on DialogModal with the useModal controller.`,
			},
		},
	},
	// The story's template owns the controller through `useModal`; these are placeholders.
	args: {
		controller: {
			id: 'storybook-confirm',
			dialogRef: { current: null },
			isOpen: false,
			isRendered: false,
			open: fn(),
			close: fn(),
		},
		title: 'Delete item',
		ariaLabel: 'Delete item',
		confirmLabel: 'Delete',
		cancelLabel: 'Cancel',
		onConfirm: fn(),
		onCancel: fn(),
	},
	argTypes: {
		controller: { control: false },
		onConfirm: { control: false },
		onCancel: { control: false },
	},
} satisfies Meta<typeof ConfirmModal>;

export default meta;
type Story = StoryObj<typeof meta>;

type TemplateProps = ComponentProps<typeof ConfirmModal> & {
	/** Unique per story, so the docs page never holds two dialogs with the same id. */
	dialogId: string;
};

const Template = ({ dialogId, controller: _placeholder, onConfirm, ...args }: TemplateProps) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef, { id: dialogId });

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				Open Confirm
			</Button>
			<ConfirmModal
				{...args}
				controller={controller}
				onConfirm={() => {
					onConfirm();
					controller.close();
				}}
			>
				<p style={{ margin: 0 }}>This action cannot be undone.</p>
			</ConfirmModal>
		</>
	);
};

/** Resolves once the dialog is open: it mounts closed, and `showModal()` runs a moment later. */
const findOpenDialog = async () => {
	const dialog = await within(document.body).findByRole('dialog', { name: 'Delete item' });

	await waitFor(() => expect(dialog).toHaveProperty('open', true));
	await waitFor(() => expect(dialog).toBeVisible());

	return dialog;
};

const waitUntilGone = async () => {
	await waitFor(() => expect(within(document.body).queryByRole('dialog')).not.toBeInTheDocument());
};

export const Default: Story = {
	render: (args) => <Template {...args} dialogId="storybook-confirm-default" />,
};

export const DangerConfirm: Story = {
	render: (args) => <Template {...args} dialogId="storybook-confirm-danger" />,
	args: { confirmVariant: 'danger' },
};

/** Confirm calls `onConfirm`, Cancel calls `onCancel`, and both close the dialog. */
export const ConfirmAndCancel: Story = {
	render: (args) => <Template {...args} dialogId="storybook-confirm-and-cancel" />,
	play: async ({ args, canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Open Confirm' });

		await userEvent.click(trigger);
		let dialog = await findOpenDialog();
		await userEvent.click(within(dialog).getByRole('button', { name: 'Cancel' }));
		await expect(args.onCancel).toHaveBeenCalledTimes(1);
		await expect(args.onConfirm).not.toHaveBeenCalled();
		await waitUntilGone();

		await userEvent.click(trigger);
		dialog = await findOpenDialog();
		await userEvent.click(within(dialog).getByRole('button', { name: 'Delete' }));
		await expect(args.onConfirm).toHaveBeenCalledTimes(1);
		await expect(args.onCancel).toHaveBeenCalledTimes(1);
		await waitUntilGone();
	},
};

/** While the confirmation is running the confirm button is disabled and says so. */
export const Loading: Story = {
	render: (args) => <Template {...args} dialogId="storybook-confirm-loading" />,
	args: { isConfirmLoading: true },
	play: async ({ args, canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'Open Confirm' }));
		const dialog = await findOpenDialog();
		const confirm = within(dialog).getByRole('button', { name: 'Working...' });

		await expect(confirm).toBeDisabled();
		await expect(within(dialog).getByRole('button', { name: 'Cancel' })).toBeEnabled();
		await expect(args.onConfirm).not.toHaveBeenCalled();
	},
};

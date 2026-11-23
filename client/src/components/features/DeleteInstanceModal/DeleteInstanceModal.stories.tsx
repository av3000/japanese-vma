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

import type { ComponentProps } from 'react';
import { useRef } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/FormControls';
import { Stack } from '@/components/shared/layout';
import { useModal } from '@/hooks/useModal';
import { DialogModal } from './DialogModal';

const meta = {
	component: DialogModal,
	title: 'Shared/DialogModal',
	parameters: {
		docs: {
			description: {
				component: `Dialog modal built on the native \`dialog\` element. Use it with the \`useModal\` controller for open/close control.`,
			},
		},
	},
	// The story's template owns the dialog's id, ref and open state through `useModal`; these are placeholders.
	args: { id: 'storybook-dialog', dialogRef: { current: null }, onClose: fn(), children: null },
	argTypes: {
		id: { control: false },
		dialogRef: { control: false },
		onClose: { control: false },
		children: { control: false },
		isOpen: { control: false },
	},
} satisfies Meta<typeof DialogModal>;

export default meta;
type Story = StoryObj<typeof meta>;

type TemplateProps = Partial<ComponentProps<typeof DialogModal>> & {
	/** Unique per story, so the docs page never holds two dialogs with the same id. */
	dialogId: string;
	triggerLabel: string;
};

const FullTemplate = ({ dialogId, triggerLabel, ...args }: TemplateProps) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef, { id: dialogId });

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				{triggerLabel}
			</Button>
			{controller.isRendered && (
				<DialogModal
					{...args}
					id={controller.id}
					dialogRef={controller.dialogRef}
					isOpen={controller.isOpen}
					onClose={controller.close}
				>
					<DialogModal.Header>
						<DialogModal.Title>Invite collaborators</DialogModal.Title>
					</DialogModal.Header>
					<DialogModal.Body>
						<Stack gap="md">
							<p style={{ margin: 0 }}>
								Share this article with your teammates by adding their email addresses.
							</p>
							<Input aria-label="Email address" placeholder="alex@example.com" />
						</Stack>
					</DialogModal.Body>
					<DialogModal.Footer>
						<Button variant="secondary" onClick={controller.close}>
							Cancel
						</Button>
						<Button variant="primary">Send Invite</Button>
					</DialogModal.Footer>
				</DialogModal>
			)}
		</>
	);
};

const BodyOnlyTemplate = ({ dialogId, triggerLabel, ...args }: TemplateProps) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef, { id: dialogId });

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				{triggerLabel}
			</Button>
			{controller.isRendered && (
				<DialogModal
					{...args}
					id={controller.id}
					dialogRef={controller.dialogRef}
					isOpen={controller.isOpen}
					onClose={controller.close}
				>
					<p style={{ margin: 0 }}>This dialog uses body-only mode with no Header/Footer.</p>
				</DialogModal>
			)}
		</>
	);
};

/** Resolves once the dialog is open: it mounts closed, and `showModal()` runs a moment later. */
const findOpenDialog = async (name: string) => {
	const dialog = await within(document.body).findByRole('dialog', { name });

	await waitFor(() => expect(dialog).toHaveProperty('open', true));
	await waitFor(() => expect(dialog).toBeVisible());

	return dialog;
};

/**
 * The browser turns a real Escape press into `cancel` on the dialog. user-event's Escape is synthetic and
 * never reaches a native dialog, so the story fires the event the browser would.
 */
const pressEscape = (dialog: HTMLElement) => {
	dialog.dispatchEvent(new Event('cancel', { cancelable: true }));
};

const waitUntilGone = async () => {
	await waitFor(() => expect(within(document.body).queryByRole('dialog')).not.toBeInTheDocument());
};

export const Default: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-dialog-default" triggerLabel="Open dialog" />,
	args: { size: 'md', ariaLabel: 'Invite collaborators' },
	parameters: {
		docs: {
			description: {
				story: `Shows the standard header/title/body/footer layout with the close button enabled.`,
			},
		},
	},
};

export const BodyOnly: Story = {
	render: (args) => (
		<BodyOnlyTemplate {...args} dialogId="storybook-dialog-body-only" triggerLabel="Open compact dialog" />
	),
	args: {
		size: 'sm',
		closeOnBackdrop: true,
		ariaLabel: 'Confirm action',
	},
	parameters: {
		docs: {
			description: {
				story: `Demonstrates a body-only dialog with a smaller size variant.`,
			},
		},
	},
};

/** The trigger opens a modal dialog, Escape closes it, and focus goes back to the trigger. */
export const OpensAndCloses: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-dialog-opens" triggerLabel="Open dialog" />,
	args: { ariaLabel: 'Invite collaborators' },
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Open dialog' });

		await userEvent.click(trigger);
		const dialog = await findOpenDialog('Invite collaborators');
		await expect(dialog).toBeVisible();
		await waitFor(() =>
			expect(within(dialog).getByRole('heading', { name: 'Invite collaborators' })).toBeVisible(),
		);

		pressEscape(dialog);
		await waitUntilGone();
		await waitFor(() => expect(trigger).toHaveFocus());
	},
};

/** The close button closes the dialog and also returns focus to the trigger. */
export const CloseButton: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-dialog-close-button" triggerLabel="Open dialog" />,
	args: { ariaLabel: 'Invite collaborators', closeLabel: 'Close invite dialog' },
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Open dialog' });

		await userEvent.click(trigger);
		const dialog = await findOpenDialog('Invite collaborators');

		await userEvent.click(within(dialog).getByRole('button', { name: 'Close invite dialog' }));
		await waitUntilGone();
		await waitFor(() => expect(trigger).toHaveFocus());
	},
};

/** With `closeOnBackdrop` a click on the backdrop closes the dialog. */
export const BackdropClose: Story = {
	render: (args) => (
		<BodyOnlyTemplate {...args} dialogId="storybook-dialog-backdrop-close" triggerLabel="Open dialog" />
	),
	args: { size: 'sm', closeOnBackdrop: true, ariaLabel: 'Confirm action' },
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Open dialog' });

		await userEvent.click(trigger);
		const dialog = await findOpenDialog('Confirm action');

		// The backdrop is decorative (`aria-hidden`), so it has no role to query: it is the dialog's first child.
		await userEvent.click(dialog.firstElementChild as HTMLElement);
		await waitUntilGone();
	},
};

/** Without `closeOnBackdrop` the same click leaves the dialog open. */
export const BackdropIgnored: Story = {
	render: (args) => (
		<BodyOnlyTemplate {...args} dialogId="storybook-dialog-backdrop-ignored" triggerLabel="Open dialog" />
	),
	args: { size: 'sm', closeOnBackdrop: false, ariaLabel: 'Confirm action' },
	play: async ({ canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'Open dialog' }));
		const dialog = await findOpenDialog('Confirm action');

		// Closing is synchronous (`dialog.close()` runs in the click handler); only the unmount waits for the transition.
		await userEvent.click(dialog.firstElementChild as HTMLElement);
		await expect(dialog).toBeVisible();
		await expect(dialog).toHaveProperty('open', true);
	},
};

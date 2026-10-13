import type { ComponentProps } from 'react';
import { useRef } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/FormControls';
import { Stack } from '@/components/shared/layout';
import { useDialog } from '@/hooks/useDialog';
import { Modal } from './Modal';

const meta = {
	component: Modal,
	title: 'Shared/Modal',
	parameters: {
		docs: {
			description: {
				component: `Shared modal built on the native \`dialog\` element. Use it with the \`useDialog\` hook for open/close control.`,
			},
		},
	},
	// The story's template owns the dialog's id, ref and open state through `useDialog`; these are placeholders.
	args: { id: 'storybook-modal', dialogRef: { current: null }, handleClose: fn(), children: null },
	argTypes: {
		id: { control: false },
		dialogRef: { control: false },
		handleClose: { control: false },
		children: { control: false },
		isOpen: { control: false },
	},
} satisfies Meta<typeof Modal>;

export default meta;
type Story = StoryObj<typeof meta>;

type TemplateProps = Partial<ComponentProps<typeof Modal>> & {
	/** Unique per story, so the docs page never holds two dialogs with the same id. */
	dialogId: string;
	triggerLabel: string;
};

const FullTemplate = ({ dialogId, triggerLabel, ...args }: TemplateProps) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const { isOpen, isRendered, handleOpen, handleClose } = useDialog(dialogRef);

	return (
		<>
			<Button variant="secondary" onClick={handleOpen}>
				{triggerLabel}
			</Button>
			{isRendered && (
				<Modal {...args} id={dialogId} dialogRef={dialogRef} isOpen={isOpen} handleClose={handleClose}>
					<Modal.Header>
						<Modal.Title>Invite collaborators</Modal.Title>
					</Modal.Header>
					<Modal.Body>
						<Stack gap="md">
							<p style={{ margin: 0 }}>
								Share this article with your teammates by adding their email addresses.
							</p>
							<Input aria-label="Email address" placeholder="alex@example.com" />
						</Stack>
					</Modal.Body>
					<Modal.Footer>
						<Button variant="secondary" onClick={handleClose}>
							Cancel
						</Button>
						<Button variant="primary">Send Invite</Button>
					</Modal.Footer>
				</Modal>
			)}
		</>
	);
};

const OptionalSectionsTemplate = ({ dialogId, triggerLabel, ...args }: TemplateProps) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const { isOpen, isRendered, handleOpen, handleClose } = useDialog(dialogRef);

	return (
		<>
			<Button variant="secondary" onClick={handleOpen}>
				{triggerLabel}
			</Button>
			{isRendered && (
				<Modal
					{...args}
					id={dialogId}
					dialogRef={dialogRef}
					isOpen={isOpen}
					handleClose={handleClose}
					ariaLabel="Confirm action"
				>
					<Modal.Body>
						<p style={{ margin: 0 }}>
							This modal skips the header entirely. Provide an `ariaLabel` when there is no title.
						</p>
					</Modal.Body>
					<Modal.Footer>
						<Button variant="secondary" onClick={handleClose}>
							Not now
						</Button>
						<Button variant="success">Confirm</Button>
					</Modal.Footer>
				</Modal>
			)}
		</>
	);
};

const InfoOnlyTemplate = ({ dialogId, triggerLabel, ...args }: TemplateProps) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const { isOpen, isRendered, handleOpen, handleClose } = useDialog(dialogRef);

	return (
		<>
			<Button variant="secondary" onClick={handleOpen}>
				{triggerLabel}
			</Button>
			{isRendered && (
				<Modal {...args} id={dialogId} dialogRef={dialogRef} isOpen={isOpen} handleClose={handleClose}>
					<Modal.Header>
						<Modal.Title>Information to read</Modal.Title>
					</Modal.Header>
					<Modal.Body>
						<p style={{ margin: 0 }}>
							This modal skips the footer entirely. Lorem ipsum dolor sit amet consectetur adipisicing
							elit. Esse corporis labore dignissimos molestias libero repellat, unde eos corrupti, dolore
							reprehenderit facere in aliquid quis minus adipisci natus? Sint, provident nobis! Lorem
							ipsum dolor sit amet consectetur adipisicing elit. Esse corporis labore dignissimos
							molestias libero repellat, unde eos corrupti, dolore reprehenderit facere in aliquid quis
							minus adipisci natus? Sint, provident nobis! Lorem ipsum dolor sit amet consectetur
							adipisicing elit. Esse corporis labore dignissimos molestias libero repellat, unde eos
							corrupti, dolore reprehenderit facere in aliquid quis minus adipisci natus? Sint, provident
							nobis!
						</p>
					</Modal.Body>
				</Modal>
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

const waitUntilGone = async () => {
	await waitFor(() => expect(within(document.body).queryByRole('dialog')).not.toBeInTheDocument());
};

export const Default: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-modal-default" triggerLabel="Open modal" />,
	args: { size: 'md' },
	parameters: {
		docs: {
			description: {
				story: `Shows the standard header/title/body/footer layout with the close button enabled.`,
			},
		},
	},
};

export const OptionalSections: Story = {
	render: (args) => (
		<OptionalSectionsTemplate {...args} dialogId="storybook-modal-optional" triggerLabel="Open compact modal" />
	),
	args: {
		size: 'sm',
		closeOnBackdrop: true,
	},
	parameters: {
		docs: {
			description: {
				story: `Demonstrates optional sections and a smaller size variant.`,
			},
		},
	},
};

export const InfoOnly: Story = {
	render: (args) => (
		<InfoOnlyTemplate {...args} dialogId="storybook-modal-info-only" triggerLabel="Open info-only modal" />
	),
	args: {
		size: 'md',
		closeOnBackdrop: true,
	},
	parameters: {
		docs: {
			description: {
				story: `Demonstrates Modal with body only for information display and medium size variant`,
			},
		},
	},
};

/**
 * The trigger opens a modal dialog named by its title, Cancel closes it, and focus goes back to the trigger.
 * Escape is not asserted: user-event cannot send the trusted key the browser turns into `cancel`, and
 * `useDialog` attaches its `cancel` handler only to a dialog that is mounted when the hook first runs.
 */
export const OpensAndCloses: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-modal-opens" triggerLabel="Open modal" />,
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Open modal' });

		await userEvent.click(trigger);
		const dialog = await findOpenDialog('Invite collaborators');
		await expect(dialog).toBeVisible();

		await userEvent.click(within(dialog).getByRole('button', { name: 'Cancel' }));
		await waitUntilGone();
		await waitFor(() => expect(trigger).toHaveFocus());
	},
};

/** The header's close button closes the modal, and `Cancel` in the footer does the same. */
export const CloseButton: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-modal-close-button" triggerLabel="Open modal" />,
	args: { closeLabel: 'Close invite modal' },
	play: async ({ canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'Open modal' }));
		const dialog = await findOpenDialog('Invite collaborators');

		await userEvent.click(within(dialog).getByRole('button', { name: 'Close invite modal' }));
		await waitUntilGone();
	},
};

/** With `closeOnBackdrop` a click on the backdrop closes the modal. */
export const BackdropClose: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-modal-backdrop-close" triggerLabel="Open modal" />,
	args: { closeOnBackdrop: true },
	play: async ({ canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'Open modal' }));
		const dialog = await findOpenDialog('Invite collaborators');

		// The backdrop is decorative (`aria-hidden`), so it has no role to query: it is the dialog's first child.
		await userEvent.click(dialog.firstElementChild as HTMLElement);
		await waitUntilGone();
	},
};

/** Without `closeOnBackdrop` the same click leaves the modal open. */
export const BackdropIgnored: Story = {
	render: (args) => <FullTemplate {...args} dialogId="storybook-modal-backdrop-ignored" triggerLabel="Open modal" />,
	args: { closeOnBackdrop: false },
	play: async ({ canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'Open modal' }));
		const dialog = await findOpenDialog('Invite collaborators');

		// Closing is synchronous (`dialog.close()` runs in the click handler); only the unmount waits for the transition.
		await userEvent.click(dialog.firstElementChild as HTMLElement);
		await expect(dialog).toBeVisible();
		await expect(dialog).toHaveProperty('open', true);
	},
};

import type { ComponentProps } from 'react';
import { useRef } from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { DialogModal } from './DialogModal';

const meta: Meta<typeof DialogModal> = {
	component: DialogModal,
	title: 'Components/DialogModal',
	tags: ['autodocs'],
	parameters: {
		docs: {
			description: {
				component: `Dialog modal built on the native \`dialog\` element. Use it with the \`useModal\` controller for open/close control.`,
			},
		},
	},
};

export default meta;
type Story = StoryObj<typeof DialogModal>;

const DefaultTemplate = (args: Partial<ComponentProps<typeof DialogModal>>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef);

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				Open Dialog
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
						<p className="mb-3">Share this article with your teammates by adding their email addresses.</p>
						<input className="form-control" placeholder="alex@example.com" />
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

export const Default: Story = {
	render: (args) => <DefaultTemplate {...args} />,
	args: {
		size: 'md',
	},
	parameters: {
		docs: {
			description: {
				story: `Shows the standard header/title/body/footer layout with the close button enabled.`,
			},
		},
	},
};

const BodyOnlyTemplate = (args: Partial<ComponentProps<typeof DialogModal>>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef);

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				Open Compact Dialog
			</Button>
			{controller.isRendered && (
				<DialogModal
					{...args}
					id={controller.id}
					dialogRef={controller.dialogRef}
					isOpen={controller.isOpen}
					onClose={controller.close}
					ariaLabel="Confirm action"
				>
					<p className="mb-0">This dialog uses body-only mode with no Header/Footer.</p>
				</DialogModal>
			)}
		</>
	);
};

export const BodyOnly: Story = {
	render: (args) => <BodyOnlyTemplate {...args} />,
	args: {
		size: 'sm',
		closeOnBackdrop: true,
	},
	parameters: {
		docs: {
			description: {
				story: `Demonstrates a body-only dialog with a smaller size variant.`,
			},
		},
	},
};

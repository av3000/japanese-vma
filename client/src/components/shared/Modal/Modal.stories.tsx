import type { ComponentProps } from 'react';
import { useRef } from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '@/components/shared/Button';
import { useDialog } from '@/hooks/useDialog';
import { Modal } from './Modal';

const meta: Meta<typeof Modal> = {
	component: Modal,
	title: 'Components/Modal',
	tags: ['autodocs'],
	parameters: {
		docs: {
			description: {
				component: `Shared modal built on the native \`dialog\` element. Use it with the \`useDialog\` hook for open/close control.`,
			},
		},
	},
};

export default meta;
type Story = StoryObj<typeof Modal>;

const DefaultTemplate = (args: Partial<ComponentProps<typeof Modal>>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const { isOpen, isRendered, handleOpen, handleClose } = useDialog(dialogRef);
	const controlId = 'storybook-modal-default';

	return (
		<>
			<Button variant="secondary" onClick={handleOpen}>
				Open Modal
			</Button>
			{isRendered && (
				<Modal {...args} id={controlId} dialogRef={dialogRef} isOpen={isOpen} handleClose={handleClose}>
					<Modal.Header>
						<Modal.Title>Invite collaborators</Modal.Title>
					</Modal.Header>
					<Modal.Body>
						<p className="mb-3">Share this article with your teammates by adding their email addresses.</p>
						<input className="form-control" placeholder="alex@example.com" />
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

const OptionalSectionsTemplate = (args: Partial<ComponentProps<typeof Modal>>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const { isOpen, isRendered, handleOpen, handleClose } = useDialog(dialogRef);
	const controlId = 'storybook-modal-optional';

	return (
		<>
			<Button variant="secondary" onClick={handleOpen}>
				Open Compact Modal
			</Button>
			{isRendered && (
				<Modal
					{...args}
					id={controlId}
					dialogRef={dialogRef}
					isOpen={isOpen}
					handleClose={handleClose}
					ariaLabel="Confirm action"
				>
					<Modal.Body>
						<p className="mb-0">
							This modal skips the header entirely. Provide an \`ariaLabel\` when there is no title.
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

export const OptionalSections: Story = {
	render: (args) => <OptionalSectionsTemplate {...args} />,
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

const ModalOnlyInfo = (args: Partial<ComponentProps<typeof Modal>>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const { isOpen, isRendered, handleOpen, handleClose } = useDialog(dialogRef);
	const controlId = 'storybook-modal-optional';

	return (
		<>
			<Button variant="secondary" onClick={handleOpen}>
				Open Info only Modal
			</Button>
			{isRendered && (
				<Modal
					{...args}
					id={controlId}
					dialogRef={dialogRef}
					isOpen={isOpen}
					handleClose={handleClose}
					ariaLabel="Confirm action"
				>
					<Modal.Header>
						<Modal.Title>Information to read</Modal.Title>
					</Modal.Header>
					<Modal.Body>
						<p className="mb-0">
							This modal skips the footer entirely. Provide an \`ariaLabel\` when there is no title. Lorem
							ipsum dolor sit amet consectetur adipisicing elit. Esse corporis labore dignissimos
							molestias libero repellat, unde eos corrupti, dolore reprehenderit facere in aliquid quis
							minus adipisci natus? Sint, provident nobis! Lorem ipsum dolor sit amet consectetur
							adipisicing elit. Esse corporis labore dignissimos molestias libero repellat, unde eos
							corrupti, dolore reprehenderit facere in aliquid quis minus adipisci natus? Sint, provident
							nobis! Lorem ipsum dolor sit amet consectetur adipisicing elit. Esse corporis labore
							dignissimos molestias libero repellat, unde eos corrupti, dolore reprehenderit facere in
							aliquid quis minus adipisci natus? Sint, provident nobis!
						</p>
					</Modal.Body>
				</Modal>
			)}
		</>
	);
};

export const ModalOnlyInfoStory: Story = {
	render: (args) => <ModalOnlyInfo {...args} />,
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

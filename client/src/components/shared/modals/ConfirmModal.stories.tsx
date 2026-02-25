import type { ComponentProps } from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { ConfirmModal } from './ConfirmModal';

const meta: Meta<typeof ConfirmModal> = {
	component: ConfirmModal,
	title: 'Components/Modals/ConfirmModal',
	tags: ['autodocs'],
	parameters: {
		docs: {
			description: {
				component: `Standard confirm modal built on DialogModal with the useModal controller.`,
			},
		},
	},
};

export default meta;
type Story = StoryObj<typeof ConfirmModal>;

const Template = (args: Partial<ComponentProps<typeof ConfirmModal>>) => {
	const controller = useModal();

	const handleConfirm = () => {
		controller.close();
	};

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				Open Confirm
			</Button>
			<ConfirmModal
				{...args}
				controller={controller}
				title={args.title ?? 'Delete item'}
				onConfirm={handleConfirm}
			>
				{args.children ?? <p className="mb-0">This action cannot be undone.</p>}
			</ConfirmModal>
		</>
	);
};

export const Default: Story = {
	render: (args) => <Template {...args} />,
	args: {
		confirmLabel: 'Delete',
		cancelLabel: 'Cancel',
	},
};

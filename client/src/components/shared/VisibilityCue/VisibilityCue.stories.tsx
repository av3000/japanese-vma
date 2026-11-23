import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { PUBLICITY } from '@/api/publicity';
import { VisibilityCue } from './';

const meta = {
	title: 'Shared/VisibilityCue',
	component: VisibilityCue,
	args: { publicity: PUBLICITY.PUBLIC },
	argTypes: {
		publicity: { control: 'inline-radio', options: Object.values(PUBLICITY) },
	},
} satisfies Meta<typeof VisibilityCue>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Public: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Public')).toBeVisible();
	},
};

export const Private: Story = {
	args: { publicity: PUBLICITY.PRIVATE },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Private')).toBeVisible();
	},
};

export const AllVariants: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
			<VisibilityCue publicity={PUBLICITY.PUBLIC} />
			<VisibilityCue publicity={PUBLICITY.PRIVATE} />
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Public')).toBeVisible();
		await expect(canvas.getByText('Private')).toBeVisible();
	},
};

/** A missing value reads as private: showing a private item as public is the mistake to avoid. */
export const MissingReadsAsPrivate: Story = {
	args: { publicity: null },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Private')).toBeVisible();
	},
};

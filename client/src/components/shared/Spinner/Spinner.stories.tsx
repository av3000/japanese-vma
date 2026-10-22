import type { ReactNode } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import Spinner from './';

const SIZES = ['sm', 'md', 'lg'] as const;

const meta = {
	title: 'Shared/Spinner',
	component: Spinner,
	args: { size: 'md' },
	argTypes: {
		size: { control: 'inline-radio', options: SIZES },
	},
} satisfies Meta<typeof Spinner>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * The spinner has no name of its own and fills its parent. Name the parent where it is used, as here,
 * so a screen reader hears "Loading".
 */
const Framed = ({ label, children }: { label: string; children: ReactNode }) => (
	<div role="status" aria-label={label} style={{ width: '4rem', height: '4rem' }}>
		{children}
	</div>
);

export const Playground: Story = {
	render: (args) => (
		<Framed label="Loading">
			<Spinner {...args} />
		</Framed>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status', { name: 'Loading' })).toBeVisible();
	},
};

export const AllSizes: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: '2rem', alignItems: 'center' }}>
			{SIZES.map((size) => (
				<Framed key={size} label={`Loading, ${size}`}>
					<Spinner size={size} />
				</Framed>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of SIZES) {
			await expect(canvas.getByRole('status', { name: `Loading, ${size}` })).toBeVisible();
		}
	},
};

/** The spinner draws in the text colour of its parent. */
export const InheritsColour: Story = {
	args: { size: 'lg' },
	render: (args) => (
		<div style={{ color: 'var(--color-primary)' }}>
			<Framed label="Loading">
				<Spinner {...args} />
			</Framed>
		</div>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status', { name: 'Loading' })).toBeVisible();
	},
};

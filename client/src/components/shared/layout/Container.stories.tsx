import type { CSSProperties } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import type { ContainerSize } from './';
import { Container } from './Container';

/** `ContainerSize` is a type, so the list lives here and the compiler checks it against the type. */
const SIZES = ['xs', 'sm', 'md', 'lg', 'fluid'] as const satisfies readonly ContainerSize[];

const filled: CSSProperties = {
	padding: '1rem',
	background: 'var(--color-neutral-200)',
	borderRadius: 'var(--border-radius-sm)',
};

const meta = {
	title: 'Shared/Layout/Container',
	component: Container,
	parameters: { layout: 'fullscreen' },
	args: { size: 'lg', children: <div style={filled}>Content inside the container</div> },
	argTypes: {
		size: { control: 'inline-radio', options: SIZES },
		as: { control: 'select', options: ['div', 'section', 'main', 'article'] },
	},
} satisfies Meta<typeof Container>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Content inside the container')).toBeVisible();
	},
};

/** The max width per size. The container centres itself and never grows past its token. */
export const AllSizes: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{SIZES.map((size) => (
				<Container key={size} size={size}>
					<div style={filled}>size=&quot;{size}&quot;</div>
				</Container>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of SIZES) {
			await expect(canvas.getByText(`size="${size}"`)).toBeVisible();
		}
	},
};

/** `as` swaps the element, so a page can make its container the `main` landmark. */
export const AsMain: Story = {
	args: { as: 'main' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('main')).toBeVisible();
	},
};

/** On a phone every size collapses to the width of the screen. */
export const OnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{SIZES.map((size) => (
				<Container key={size} size={size}>
					<div style={filled}>size=&quot;{size}&quot;</div>
				</Container>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('size="fluid"')).toBeVisible();
	},
};

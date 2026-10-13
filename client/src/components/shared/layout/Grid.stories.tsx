import type { CSSProperties } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import type { LayoutAlign, SpacingToken } from './';
import { Grid } from './Grid';

/** The types are unions, so the lists live here and the compiler checks them against the types. */
const GAPS = [
	'none',
	'3xs',
	'2xs',
	'xs',
	'sm',
	'md',
	'lg',
	'xl',
	'2xl',
	'3xl',
	'4xl',
] as const satisfies readonly SpacingToken[];
const ALIGNS = ['start', 'center', 'end', 'baseline', 'stretch'] as const satisfies readonly LayoutAlign[];

const cell: CSSProperties = {
	padding: '0.5rem 0.75rem',
	background: 'var(--color-neutral-200)',
	borderRadius: 'var(--border-radius-sm)',
};

const cells = (count: number) =>
	Array.from({ length: count }, (_, index) => (
		<Grid.Item key={index} span={1}>
			<div style={cell}>Cell {index + 1}</div>
		</Grid.Item>
	));

const meta = {
	title: 'Shared/Layout/Grid',
	component: Grid,
	parameters: { layout: 'padded' },
	args: { columns: 3, gap: 'md', align: 'stretch', children: cells(6) },
	argTypes: {
		columns: { control: { type: 'number', min: 1, max: 12 } },
		gap: { control: 'select', options: GAPS },
		align: { control: 'select', options: ALIGNS },
	},
} satisfies Meta<typeof Grid>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Cell 1')).toBeVisible();
		await expect(canvas.getByText('Cell 6')).toBeVisible();
	},
};

export const Columns: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1.5rem' }}>
			{[1, 2, 4, 6].map((columns) => (
				<div key={columns}>
					<p style={{ margin: '0 0 0.5rem' }}>columns={columns}</p>
					<Grid columns={columns} gap="sm">
						{cells(6)}
					</Grid>
				</div>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const columns of [1, 2, 4, 6]) {
			await expect(canvas.getByText(`columns=${columns}`)).toBeVisible();
		}
	},
};

/** Counts per breakpoint: one column on a phone, two from `sm`, three from `md`, four from `lg`. */
export const ResponsiveColumns: Story = {
	args: { columns: { base: 1, sm: 2, md: 3, lg: 4 }, children: cells(8) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Cell 8')).toBeVisible();
	},
};

/** The same grid on a phone: one column. */
export const ResponsiveColumnsOnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { columns: { base: 1, sm: 2, md: 3, lg: 4 }, children: cells(4) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Cell 4')).toBeVisible();
	},
};

export const Gaps: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1.5rem' }}>
			{GAPS.map((gap) => (
				<div key={gap}>
					<p style={{ margin: '0 0 0.5rem' }}>gap=&quot;{gap}&quot;</p>
					<Grid columns={3} gap={gap}>
						{cells(3)}
					</Grid>
				</div>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const gap of GAPS) {
			await expect(canvas.getByText(`gap="${gap}"`)).toBeVisible();
		}
	},
};

/** Cross-axis alignment shows when items have different heights. */
export const Alignments: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1.5rem' }}>
			{ALIGNS.map((align) => (
				<div key={align}>
					<p style={{ margin: '0 0 0.5rem' }}>align=&quot;{align}&quot;</p>
					<Grid columns={3} gap="sm" align={align} style={{ border: '1px dashed var(--color-neutral-400)' }}>
						<Grid.Item span={1}>
							<div style={cell}>Short</div>
						</Grid.Item>
						<Grid.Item span={1}>
							<div style={{ ...cell, padding: '1.5rem 0.75rem' }}>Taller item</div>
						</Grid.Item>
						<Grid.Item span={1}>
							<div style={cell}>Short</div>
						</Grid.Item>
					</Grid>
				</div>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const align of ALIGNS) {
			await expect(canvas.getByText(`align="${align}"`)).toBeVisible();
		}
	},
};

/** A 12-track grid with item spans: a main column and a side rail that stack on a phone. */
export const ItemSpans: Story = {
	args: { columns: 12, gap: 'md' },
	render: (args) => (
		<Grid {...args}>
			<Grid.Item span={{ base: 12, md: 8 }}>
				<div style={cell}>Main content: span 12, then 8 from md</div>
			</Grid.Item>
			<Grid.Item span={{ base: 12, md: 4 }}>
				<div style={cell}>Side rail: span 12, then 4 from md</div>
			</Grid.Item>
			<Grid.Item span="auto">
				<div style={cell}>auto</div>
			</Grid.Item>
			<Grid.Item span={6}>
				<div style={cell}>span 6</div>
			</Grid.Item>
		</Grid>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText(/Main content/)).toBeVisible();
		await expect(canvas.getByText(/Side rail/)).toBeVisible();
	},
};

export const ItemSpansOnPhone: Story = {
	...ItemSpans,
	globals: { viewport: { value: 'xs' } },
};

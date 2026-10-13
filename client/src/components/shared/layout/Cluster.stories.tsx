import type { CSSProperties } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import type { LayoutAlign, LayoutJustify, SpacingToken } from './';
import { Cluster } from './Cluster';

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
const JUSTIFIES = ['start', 'center', 'end', 'between', 'around', 'evenly'] as const satisfies readonly LayoutJustify[];

const box: CSSProperties = {
	padding: '0.5rem 0.75rem',
	background: 'var(--color-neutral-200)',
	borderRadius: 'var(--border-radius-sm)',
};

const frame: CSSProperties = { border: '1px dashed var(--color-neutral-400)' };

const Boxes = () => (
	<>
		<span style={box}>N5</span>
		<span style={{ ...box, padding: '1.25rem 0.75rem' }}>Taller item</span>
		<span style={box}>漢字</span>
		<span style={box}>Grammar</span>
	</>
);

const meta = {
	title: 'Shared/Layout/Cluster',
	component: Cluster,
	parameters: { layout: 'padded' },
	args: { gap: 'xs', align: 'center', justify: 'start', wrap: true, children: <Boxes /> },
	argTypes: {
		gap: { control: 'select', options: GAPS },
		align: { control: 'select', options: ALIGNS },
		justify: { control: 'select', options: JUSTIFIES },
		wrap: { control: 'boolean' },
	},
} satisfies Meta<typeof Cluster>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Taller item')).toBeVisible();
	},
};

export const Gaps: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{GAPS.map((gap) => (
				<div key={gap}>
					<p style={{ margin: '0 0 0.5rem' }}>gap=&quot;{gap}&quot;</p>
					<Cluster gap={gap}>
						<span style={box}>One</span>
						<span style={box}>Two</span>
						<span style={box}>Three</span>
					</Cluster>
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

/** Cross-axis alignment shows when the items have different heights. */
export const Alignments: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{ALIGNS.map((align) => (
				<div key={align}>
					<p style={{ margin: '0 0 0.5rem' }}>align=&quot;{align}&quot;</p>
					<Cluster align={align} style={frame}>
						<Boxes />
					</Cluster>
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

export const Justifications: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{JUSTIFIES.map((justify) => (
				<div key={justify}>
					<p style={{ margin: '0 0 0.5rem' }}>justify=&quot;{justify}&quot;</p>
					<Cluster justify={justify} style={frame}>
						<span style={box}>One</span>
						<span style={box}>Two</span>
						<span style={box}>Three</span>
					</Cluster>
				</div>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const justify of JUSTIFIES) {
			await expect(canvas.getByText(`justify="${justify}"`)).toBeVisible();
		}
	},
};

const manyTags = Array.from({ length: 14 }, (_, index) => `Tag ${index + 1}`);

/** Wrapping is on by default: items drop to the next line. With `wrap={false}` they stay on one line and overflow. */
export const Wrapping: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem', maxWidth: '24rem' }}>
			<div>
				<p style={{ margin: '0 0 0.5rem' }}>wrap</p>
				<Cluster gap="xs" style={frame}>
					{manyTags.map((tag) => (
						<span key={tag} style={box}>
							{tag}
						</span>
					))}
				</Cluster>
			</div>
			<div style={{ overflow: 'hidden' }}>
				<p style={{ margin: '0 0 0.5rem' }}>wrap=&#123;false&#125;</p>
				<Cluster gap="xs" wrap={false} style={frame}>
					{manyTags.map((tag) => (
						<span key={tag} style={box}>
							{tag}
						</span>
					))}
				</Cluster>
			</div>
		</div>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByText('Tag 1')).toHaveLength(2);
	},
};

export const OnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	args: {
		children: manyTags.map((tag) => (
			<span key={tag} style={box}>
				{tag}
			</span>
		)),
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Tag 14')).toBeVisible();
	},
};

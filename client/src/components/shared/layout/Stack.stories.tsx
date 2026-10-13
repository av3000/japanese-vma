import type { CSSProperties } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import type { LayoutAlign, SpacingToken } from './';
import { Stack } from './Stack';

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

const box: CSSProperties = {
	padding: '0.5rem 0.75rem',
	background: 'var(--color-neutral-200)',
	borderRadius: 'var(--border-radius-sm)',
};

const Boxes = () => (
	<>
		<div style={box}>First</div>
		<div style={box}>Second, with more text</div>
		<div style={box}>Third</div>
	</>
);

const meta = {
	title: 'Shared/Layout/Stack',
	component: Stack,
	parameters: { layout: 'padded' },
	args: { gap: 'md', align: 'stretch', children: <Boxes /> },
	argTypes: {
		gap: { control: 'select', options: GAPS },
		align: { control: 'select', options: ALIGNS },
		as: { control: 'select', options: ['div', 'section', 'ul', 'ol', 'form', 'fieldset'] },
	},
} satisfies Meta<typeof Stack>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('First')).toBeVisible();
		await expect(canvas.getByText('Third')).toBeVisible();
	},
};

/** The gap is a spacing token, from `none` to `4xl`. */
export const Gaps: Story = {
	render: () => (
		<div style={{ display: 'flex', flexWrap: 'wrap', gap: '2rem', alignItems: 'flex-start' }}>
			{GAPS.map((gap) => (
				<div key={gap}>
					<p style={{ margin: '0 0 0.5rem' }}>gap=&quot;{gap}&quot;</p>
					<Stack gap={gap}>
						<div style={box}>One</div>
						<div style={box}>Two</div>
					</Stack>
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

/** Cross-axis alignment is horizontal here, since the stack is a column. */
export const Alignments: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1.5rem' }}>
			{ALIGNS.map((align) => (
				<div key={align}>
					<p style={{ margin: '0 0 0.5rem' }}>align=&quot;{align}&quot;</p>
					<Stack align={align} gap="xs" style={{ border: '1px dashed var(--color-neutral-400)' }}>
						<div style={box}>Short</div>
						<div style={box}>A little longer item</div>
					</Stack>
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

/** `as` swaps the element, so a list stays a list for assistive technology. */
export const AsList: Story = {
	render: () => (
		<Stack as="ul" gap="xs" style={{ listStyle: 'none', margin: 0, padding: 0 }}>
			<li style={box}>漢字</li>
			<li style={box}>ひらがな</li>
			<li style={box}>カタカナ</li>
		</Stack>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('listitem')).toHaveLength(3);
	},
};

export const OnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	args: {
		gap: 'sm',
		children: (
			<>
				<div style={box}>新宿駅の新しい改札口についてのお知らせとご案内</div>
				<div style={box}>Second</div>
			</>
		),
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Second')).toBeVisible();
	},
};

import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { Initials, initialsOf } from './';

const meta = {
	title: 'Shared/Initials',
	component: Initials,
	args: { name: 'Alanas Vaičiulis', size: 'md' },
	argTypes: {
		size: { control: 'inline-radio', options: ['sm', 'md'] },
	},
} satisfies Meta<typeof Initials>;

export default meta;

type Story = StoryObj<typeof meta>;

const NAMES = [
	{ name: 'Alanas Vaičiulis', note: 'first and last word' },
	{ name: 'Maria de la Cruz Santos', note: 'first and last word, middle skipped' },
	{ name: '田中 太郎', note: 'Japanese name with a space' },
	{ name: '山田', note: 'no space: one character' },
	{ name: '𠮷野 家', note: 'outside the BMP, not split' },
	{ name: '  ', note: 'blank' },
	{ name: null, note: 'no name' },
] as const;

/** Decorative: the name always sits beside it, so assistive technology skips the circle. */
export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('AV')).toHaveAttribute('aria-hidden', 'true');
	},
};

export const Sizes: Story = {
	render: (args) => (
		<div style={{ display: 'flex', gap: '0.75rem', alignItems: 'center' }}>
			<Initials {...args} size="sm" />
			<Initials {...args} size="md" />
		</div>
	),
};

/** How a name becomes initials, including Japanese names, blank and missing values. */
export const Names: Story = {
	render: (args) => (
		<ul style={{ listStyle: 'none', margin: 0, padding: 0, display: 'grid', gap: '0.5rem' }}>
			{NAMES.map(({ name, note }) => (
				<li key={String(name)} style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
					<Initials {...args} name={name} />
					<span>
						{name?.trim() || 'No name'} ({note})
					</span>
				</li>
			))}
		</ul>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Maria de la Cruz Santos (first and last word, middle skipped)')).toBeVisible();
		await expect(initialsOf('田中 太郎')).toBe('田太');
		await expect(initialsOf('山田')).toBe('山');
		await expect(initialsOf('𠮷野 家')).toBe('𠮷家');
		await expect(initialsOf(null)).toBe('?');
	},
};

import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { JLPT_LEVELS, LevelBadge } from './';

const meta = {
	title: 'Shared/LevelBadge',
	component: LevelBadge,
	args: { level: 'N3', size: 'md' },
	argTypes: {
		level: { control: 'select', options: [...JLPT_LEVELS, 'uncommon'] },
		size: { control: 'inline-radio', options: ['sm', 'md'] },
		count: { control: 'number' },
	},
} satisfies Meta<typeof LevelBadge>;

export default meta;

type Story = StoryObj<typeof meta>;

const row = { display: 'flex', flexWrap: 'wrap', gap: '0.75rem', alignItems: 'center' } as const;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('N3')).toBeVisible();
	},
};

/** Every JLPT level, plus `uncommon` for material outside the lists, which is neutral grey. */
export const AllLevels: Story = {
	render: (args) => (
		<div style={row}>
			{[...JLPT_LEVELS, 'uncommon' as const].map((level) => (
				<LevelBadge key={level} {...args} level={level} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const label of ['N1', 'N2', 'N3', 'N4', 'N5', 'Uncommon']) {
			await expect(canvas.getByText(label)).toBeVisible();
		}
	},
};

export const Sizes: Story = {
	render: (args) => (
		<div style={row}>
			<LevelBadge {...args} level="N2" size="sm" />
			<LevelBadge {...args} level="N2" size="md" />
		</div>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByText('N2')).toHaveLength(2);
	},
};

/** The API sends the bare number; `n3` and `N3` are accepted too, and all read `N3`. */
export const ApiValues: Story = {
	render: (args) => (
		<div style={row}>
			<LevelBadge {...args} level={3} />
			<LevelBadge {...args} level="3" />
			<LevelBadge {...args} level="n3" />
		</div>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByText('N3')).toHaveLength(3);
	},
};

/** With a count, as on an article's kanji by level: the badge is named "N3: 12" and the digits are not read twice. */
export const WithCount: Story = {
	render: (args) => (
		<div style={row}>
			{JLPT_LEVELS.map((level, index) => (
				<LevelBadge key={level} {...args} level={level} count={(index + 1) * 12} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByLabelText('N1: 12')).toBeVisible();
		await expect(canvas.getByLabelText('N5: 60')).toBeVisible();
	},
};

export const SmallWithCount: Story = {
	args: { level: 'N1', count: 0, size: 'sm' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByLabelText('N1: 0')).toBeVisible();
	},
};

/**
 * A value that is not a level is shown as it came, upper-cased, and an empty value renders an empty
 * pill. Callers should leave the badge out instead.
 */
export const UnknownAndEmpty: Story = {
	render: (args) => (
		<div style={row}>
			<LevelBadge {...args} level="N6" />
			<LevelBadge {...args} level="" />
		</div>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('N6')).toBeVisible();
	},
};

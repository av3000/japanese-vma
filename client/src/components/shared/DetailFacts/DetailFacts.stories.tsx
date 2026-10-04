import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { Button } from '@/components/shared/Button';
import { DetailActionGroup, DetailActions } from '@/components/shared/DetailActions';
import { JlptBar } from '@/components/shared/JlptBar';
import { StatusPill, articleStatusPill } from '@/components/shared/StatusPill';
import { DetailFacts } from './';

const meta = {
	title: 'Shared/DetailFacts',
	component: DetailFacts,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: 280 }}>
				<Story />
			</div>
		),
	],
	args: {
		title: 'In this reading',
		facts: [
			{ term: 'Kanji', value: 171 },
			{ term: 'Words', value: 233 },
			{ term: 'Characters', value: 1347 },
		],
	},
} satisfies Meta<typeof DetailFacts>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { name: 'In this reading' })).toBeVisible();
		await expect(canvas.getByText('1,347')).toBeVisible();
	},
};

export const WithJlptBar: Story = {
	args: {
		children: <JlptBar levels={{ n1: 12, n2: 30, n3: 41, n4: 28, n5: 20, uncommon: 40 }} />,
	},
};

/** While processing runs, kanji and words read "Counting…" instead of a misleading 0. */
export const Counting: Story = {
	args: {
		facts: [
			{ term: 'Kanji', value: null },
			{ term: 'Words', value: null },
			{ term: 'Characters', value: 1347 },
		],
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByText('Counting…')).toHaveLength(2);
	},
};

export const Zero: Story = {
	args: {
		facts: [
			{ term: 'Kanji', value: 0 },
			{ term: 'Words', value: 0 },
			{ term: 'Characters', value: 0 },
		],
	},
};

export const EightDigitCount: Story = {
	args: { facts: [{ term: 'Characters', value: 12345678 }] },
};

/** The whole rail as Article detail composes it, for an owner who is also an admin. */
export const RailWithActions: Story = {
	render: (args) => (
		<div style={{ display: 'grid', gap: 'var(--spacing-lg)' }}>
			<DetailFacts {...args} />
			<DetailActions>
				<Button variant="outline" isFullWidth aria-pressed={false}>
					Like · 3
				</Button>
				<Button variant="outline" isFullWidth>
					Save to a catalogue
				</Button>
				<Button variant="outline" isFullWidth>
					Kanji &amp; words PDF
				</Button>
				<DetailActionGroup heading="Your article">
					<Button variant="outline" isFullWidth>
						Edit
					</Button>
					<Button variant="outline" isFullWidth>
						Delete
					</Button>
				</DetailActionGroup>
				<DetailActionGroup heading="Moderation" meta={<StatusPill {...articleStatusPill(0)} />}>
					<Button variant="outline" isFullWidth>
						Review
					</Button>
				</DetailActionGroup>
			</DetailActions>
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('region', { name: 'Moderation' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Like · 3' })).toHaveAttribute('aria-pressed', 'false');
	},
};

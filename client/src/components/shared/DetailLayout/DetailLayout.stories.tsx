import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { DetailLayout } from './';

const LONG_TITLE = 'ウクライナで「ビシバンカの日」市民が伝統衣装で結束を確認'.repeat(4);
const PARAGRAPH =
	'ロシアによる軍事侵攻が長期化する中、ウクライナでは21日、伝統衣装を着て1日を過ごす「ビシバンカの日」を迎え、多くの人が伝統衣装を着て街に繰り出しました。';

const Box = ({ label, lines = 1 }: { label: string; lines?: number }) => (
	<div style={{ border: '1px dashed var(--color-neutral-400)', padding: 'var(--spacing-sm)' }}>
		<strong>{label}</strong>
		{Array.from({ length: lines }, (_, index) => (
			<p key={index} lang="ja" style={{ margin: 'var(--spacing-xs) 0 0' }}>
				{PARAGRAPH}
			</p>
		))}
	</div>
);

const meta = {
	title: 'Shared/DetailLayout',
	component: DetailLayout,
	tags: ['autodocs'],
	parameters: { layout: 'fullscreen' },
	args: {
		railLabel: 'About this article',
		header: (
			<div>
				<a href="/articles">← Articles</a>
				<h1 lang="ja" style={{ overflowWrap: 'anywhere' }}>
					ビシバンカの日
				</h1>
			</div>
		),
		main: <Box label="main" lines={6} />,
		facts: <Box label="facts" />,
		actions: (
			<div style={{ display: 'grid', gap: 'var(--spacing-xs)' }}>
				<button type="button">Like · 3</button>
				<button type="button">Save to a catalogue</button>
				<button type="button">Kanji &amp; words PDF</button>
			</div>
		),
		extra: <Box label="extra" />,
		after: <Box label="after" lines={2} />,
	},
} satisfies Meta<typeof DetailLayout>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const rail = canvas.getByRole('complementary', { name: 'About this article' });

		await expect(within(rail).getAllByRole('button')).toHaveLength(3);
		await expect(canvas.getAllByRole('button', { name: 'Like · 3' })).toHaveLength(1);
	},
};

/** Below 1024px the actions sit under the header, and the facts and extras follow the text. */
export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };

export const Tablet: Story = { parameters: { viewport: { defaultViewport: 'tablet' } } };

/** A 240-character title with no spaces wraps inside the reading column. */
export const LongTitle: Story = {
	args: {
		header: (
			<h1 lang="ja" style={{ overflowWrap: 'anywhere' }}>
				{LONG_TITLE}
			</h1>
		),
	},
};

/** A rail taller than the viewport is not sticky, so its lower part stays reachable. */
export const TallRail: Story = {
	args: { facts: <Box label="facts" lines={12} />, extra: <Box label="extra" lines={8} /> },
	play: async ({ canvasElement }) => {
		const rail = within(canvasElement).getByRole('complementary', { name: 'About this article' });

		await expect(rail).not.toHaveAttribute('data-sticky');
	},
};

/** Without rail slots there is no `aside`, and the reading column keeps its measure. */
export const MainOnly: Story = {
	args: { facts: undefined, actions: undefined, extra: undefined },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('complementary')).not.toBeInTheDocument();
	},
};

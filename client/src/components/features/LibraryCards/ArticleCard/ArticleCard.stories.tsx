import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { LONGEST_JAPANESE_TITLE, libraryArticles, makeArticle } from '@/components/features/Homepage/fixtures';
import { ArticleCard } from './';

const meta = {
	title: 'Features/LibraryCards/ArticleCard',
	component: ArticleCard,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { article: makeArticle() },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '22rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof ArticleCard>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 2, name: '春の京都を歩く' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: '春の京都を歩く' })).toHaveAttribute(
			'href',
			'/articles/article-1',
		);
		await expect(canvas.getByRole('img', { name: /^Mostly N5:/ })).toBeVisible();
	},
};

/** Two-line clamp on the title, three tags and "+5", four-digit counts. */
export const LongestTitle: Story = {
	args: { article: libraryArticles.longest },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const title = canvas.getByRole('heading', { level: 2, name: LONGEST_JAPANESE_TITLE });
		const lineHeight = parseFloat(getComputedStyle(title).lineHeight);
		await expect(title.getBoundingClientRect().height).toBeLessThanOrEqual(lineHeight * 2 + 1);
		await expect(within(canvas.getByRole('list', { name: 'Tags' })).getAllByRole('listitem')).toHaveLength(4);
		await expect(canvas.getByRole('list', { name: 'Stats' })).toHaveTextContent('1,284 views');
	},
};

/** Kana-only title, no English title, no tags, nothing counted: 記 on the cover, no badge, no bar. */
export const ZeroEverything: Story = {
	args: { article: libraryArticles.zeroEverything },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('記')).toBeVisible();
		await expect(canvas.queryByRole('img')).not.toBeInTheDocument();
		await expect(canvas.queryByRole('list', { name: 'Tags' })).not.toBeInTheDocument();
		await expect(canvas.getByRole('list', { name: 'Stats' })).toHaveTextContent('0 views');
	},
};

/** The pill sits on the cover and stays readable; the glyph is still visible. */
export const Processing: Story = {
	args: { article: libraryArticles.processing },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Processing')).toBeVisible();
		await expect(canvas.getByText('台')).toBeVisible();
	},
};

export const Mobile: Story = {
	args: { article: libraryArticles.longest },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

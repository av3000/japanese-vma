import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { communityPosts } from '../fixtures';
import { PostCard } from './';

const meta = {
	title: 'Features/Community/PostCard',
	component: PostCard,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { post: communityPosts.default },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '48rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof PostCard>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A FAQ post: the topic tag, the title link, author and date, tags and counts. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link')).toHaveAttribute('href', '/community/post-41');
		await expect(canvas.getByText('FAQ')).toBeVisible();
		await expect(canvas.queryByText('Locked')).toBeNull();
	},
};

/** A locked announcement: the Locked pill carries the word, not only an icon or colour. */
export const Locked: Story = {
	args: { post: communityPosts.locked },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Locked')).toBeVisible();
	},
};

/** 255 characters with no spaces, a long author name, five tags and an 8-digit view count. */
export const LongestTitle: Story = {
	args: { post: communityPosts.longest },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('+2')).toBeVisible();
	},
};

/** A Japanese title is marked `lang="ja"`. */
export const JapaneseTitle: Story = {
	args: { post: communityPosts.japanese },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('link')).toHaveAttribute('lang', 'ja');
	},
};

/** No tags and no engagement yet: the counts read 0. */
export const ZeroCounts: Story = {
	args: { post: communityPosts.empty },
};

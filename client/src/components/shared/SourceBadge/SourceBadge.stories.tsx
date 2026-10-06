import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { SourceBadge } from './';

const meta = {
	title: 'Shared/SourceBadge',
	component: SourceBadge,
	tags: ['autodocs'],
	args: { source: { key: 'nhk-news', name: 'NHK News', homepage_url: 'https://news.web.nhk/newsweb' } },
} satisfies Meta<typeof SourceBadge>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const badge = within(canvasElement).getByTitle('Imported from NHK News');
		await expect(badge).toBeVisible();
		await expect(badge).toHaveTextContent('Imported from NHK News');
	},
};

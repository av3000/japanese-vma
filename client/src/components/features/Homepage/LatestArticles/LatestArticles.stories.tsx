import type { Meta, StoryObj } from '@storybook/react';
import { latestArticles } from '../fixtures';
import { LatestArticlesView } from './';

const meta = {
	title: 'Features/Homepage/LatestArticles',
	component: LatestArticlesView,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { status: 'success', articles: latestArticles, total: 128, onRetry: () => undefined },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '36rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof LatestArticlesView>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Includes the longest realistic Japanese title (clamped to two lines) and a still-processing article (no bar). */
export const Loaded: Story = {};

export const Loading: Story = { args: { status: 'pending', articles: [], total: undefined } };

export const Empty: Story = { args: { articles: [], total: 0 } };

export const ErrorState: Story = { name: 'Error', args: { status: 'error', articles: [], total: undefined } };

export const MobileViewport: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };

import type { Meta, StoryObj } from '@storybook/react';
import type { CorpusStatsResource } from '@/api/generated/model';
import { CorpusStatsTilesView } from './';

const stats: CorpusStatsResource = { radicals: 214, kanjis: 13108, words: 183512, sentences: 149862 };

const meta = {
	title: 'Features/Homepage/CorpusStatsTiles',
	component: CorpusStatsTilesView,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
} satisfies Meta<typeof CorpusStatsTilesView>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = { args: { status: 'success', stats } };

/** Skeleton tiles of the final size, so nothing moves when the counts arrive. */
export const Loading: Story = { args: { status: 'pending' } };

/** Renders nothing: the row disappears silently. */
export const ErrorState: Story = { name: 'Error', args: { status: 'error' } };

export const MobileViewport: Story = {
	args: { status: 'success', stats },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

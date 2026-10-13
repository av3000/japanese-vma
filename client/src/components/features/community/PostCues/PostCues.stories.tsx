import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { POST_TOPIC_OPTIONS } from '@/api/posts/reads';
import { StatusPill } from '@/components/shared/StatusPill';
import PostCues, { LOCKED_POST_PILL, PostTopicTag } from './';

const meta = {
	title: 'Features/Community/PostCues',
	component: PostCues,
	parameters: { layout: 'padded' },
	args: { post: { topic: 3, topic_label: 'FAQ', locked: false } },
} satisfies Meta<typeof PostCues>;

export default meta;

type Story = StoryObj<typeof meta>;

/** An open post: the topic tag alone. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('FAQ')).toBeVisible();
		await expect(canvas.queryByText('Locked')).toBeNull();
	},
};

/** The topic tag on its own: "Topic:" is read out, so the tag means something without its context. */
export const TopicTag: Story = {
	render: () => <PostTopicTag label="Announcement" />,
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/Topic:/)).toBeInTheDocument();
		await expect(within(canvasElement).getByText('Announcement')).toBeVisible();
	},
};

/** The Locked pill on its own: neutral, with the word, because locking is a state and not an error. */
export const LockedPill: Story = {
	render: () => <StatusPill {...LOCKED_POST_PILL} />,
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Locked')).toBeVisible();
	},
};

/** All the cues together: the topic tag and the Locked pill. */
export const Locked: Story = {
	args: { post: { topic: 7, topic_label: 'Announcement', locked: true } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Announcement')).toBeVisible();
		await expect(canvas.getByText('Locked')).toBeVisible();
	},
};

/** Every topic the filter offers, open and locked. */
export const EveryTopic: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '0.75rem' }}>
			{POST_TOPIC_OPTIONS.map(({ value, label }) => (
				<PostCues key={value} post={{ topic: value, topic_label: label, locked: value % 2 === 0 }} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		for (const { label } of POST_TOPIC_OPTIONS) {
			await expect(canvas.getByText(label)).toBeVisible();
		}
		await expect(canvas.getAllByText('Locked')).toHaveLength(3);
	},
};

/** The backend sent no label: the local vocabulary names the topic, and an unknown code reads "Other". */
export const MissingLabel: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '0.75rem' }}>
			<PostCues post={{ topic: 5, topic_label: null, locked: false }} />
			<PostCues post={{ topic: 99, topic_label: null, locked: false }} />
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Bug')).toBeVisible();
		await expect(canvas.getByText('Other')).toBeVisible();
	},
};

/** On a phone the cues wrap inside the width, a long label included. */
export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	args: {
		post: {
			topic: 1,
			topic_label: 'Content-related questions about 日本語能力試験の勉強方法とおすすめの教材',
			locked: true,
		},
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Locked')).toBeVisible();

		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};

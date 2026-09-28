import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { ARTICLE_STATUS } from '@/api/articles/moderation';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { articleStatusPill, processingStatusPill, STATUS_TONES, StatusPill } from './';

const meta = {
	title: 'Shared/StatusPill',
	component: StatusPill,
	tags: ['autodocs'],
	args: { tone: 'success', label: 'Approval: Approved' },
	argTypes: {
		tone: { control: 'inline-radio', options: STATUS_TONES },
	},
} satisfies Meta<typeof StatusPill>;

export default meta;

type Story = StoryObj<typeof meta>;

const grid = { display: 'flex', flexWrap: 'wrap', gap: '0.75rem', alignItems: 'center' } as const;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Approval: Approved')).toBeVisible();
	},
};

export const Tones: Story = {
	render: () => (
		<div style={grid}>
			{STATUS_TONES.map((tone) => (
				<StatusPill key={tone} tone={tone} label={tone} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		for (const tone of STATUS_TONES) {
			await expect(canvas.getByText(tone)).toBeVisible();
		}
	},
};

/** Moderation status on Article detail and the dashboard. */
export const ArticleStatuses: Story = {
	render: () => (
		<div style={grid}>
			{Object.values(ARTICLE_STATUS).map((status) => (
				<StatusPill key={status} {...articleStatusPill(status)} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		for (const { label } of Object.values(ARTICLE_STATUS).map(articleStatusPill)) {
			await expect(canvas.getByText(label)).toBeVisible();
		}
	},
};

/** Processing status on `ArticleCard` and the processing alert. Only `processing` spins. */
export const ProcessingStatuses: Story = {
	render: () => (
		<div style={grid}>
			{Object.values(ProcessingStatus).map((status) => (
				<StatusPill key={status} {...processingStatusPill(status)} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		for (const status of Object.values(ProcessingStatus)) {
			await expect(canvas.getByText(processingStatusPill(status).label)).toBeVisible();
		}
	},
};

/** Inside a narrow container the label wraps instead of overflowing. */
export const LongLabel: Story = {
	args: {
		tone: 'warning',
		label: 'Approval: Pending review by a moderator before it is published to the community',
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '14rem' }}>
				<Story />
			</div>
		),
	],
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/Pending review by a moderator/)).toBeVisible();
	},
};

import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, within } from '@storybook/test';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import ProcessingStatusAlert, { READY_ANNOUNCEMENT } from './';

const processingStatus = (status: ProcessingStatus, attempt = 1): ProcessingStatusResource => ({
	id: 1,
	entity_id: 'article-uuid',
	type: 'article_content_processing',
	status,
	sequence: 1,
	attempt,
	max_attempts: 3,
	metadata: {},
	created_at: '2026-10-03T10:00:00+00:00',
	updated_at: '2026-10-03T10:00:42+00:00',
});

const connectedSocket = { ...DEFAULT_SOCKET_CONTEXT, isConnected: true, connectionStatus: 'connected' as const };

const meta = {
	title: 'Features/Articles/ProcessingStatusAlert',
	component: ProcessingStatusAlert,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { processing_status: processingStatus(ProcessingStatus.processing) },
	decorators: [
		(Story) => (
			<SocketContext.Provider value={connectedSocket}>
				<div style={{ maxWidth: 720 }}>
					<Story />
				</div>
			</SocketContext.Provider>
		),
	],
} satisfies Meta<typeof ProcessingStatusAlert>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Pending: Story = {
	args: { processing_status: processingStatus(ProcessingStatus.pending) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText(/are queued/)).toBeVisible();
		await expect(canvas.getByText('Pending')).toBeVisible();
	},
};

/** Live: the socket is connected, so the page promises to update by itself. */
export const ProcessingLive: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/will update automatically/)).toBeVisible();
	},
};

/** Polling: no socket, so the page only promises to keep checking (#251). */
export const ProcessingPolling: Story = {
	decorators: [
		(Story) => (
			<SocketContext.Provider value={DEFAULT_SOCKET_CONTEXT}>
				<Story />
			</SocketContext.Provider>
		),
	],
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/Checking for updates/)).toBeVisible();
	},
};

export const FailedForTheOwner: Story = {
	args: { processing_status: processingStatus(ProcessingStatus.failed, 3), isOwner: true },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText(/runs it again/)).toBeVisible();
		await userEvent.click(canvas.getByText('Processing details'));
		await expect(canvas.getByText('Attempt 3 of 3')).toBeVisible();
	},
};

export const FailedForEveryoneElse: Story = {
	args: { processing_status: processingStatus(ProcessingStatus.failed) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/available for this article yet/)).toBeVisible();
	},
};

/** Completed is silent: only an empty, visually hidden live region is in the page. */
export const Completed: Story = {
	args: { processing_status: processingStatus(ProcessingStatus.completed) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByText(/kanji and vocabulary/i)).not.toBeInTheDocument();
	},
};

/** Starts processing; the button stands in for the socket event that completes it. */
const CompletingAlert: React.FC<React.ComponentProps<typeof ProcessingStatusAlert>> = (props) => {
	const [status, setStatus] = React.useState<ProcessingStatus>(ProcessingStatus.processing);

	return (
		<div style={{ display: 'grid', gap: 'var(--spacing-sm)' }}>
			<button type="button" onClick={() => setStatus(ProcessingStatus.completed)}>
				Finish processing
			</button>
			<ProcessingStatusAlert {...props} processing_status={processingStatus(status)} />
		</div>
	);
};

/** A processing article that completes while open: the alert goes and a polite status says so. */
export const CompletesWhileOpen: Story = {
	render: (args) => <CompletingAlert {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Finish processing' }));
		await expect(canvas.queryByText(/Extracting kanji/)).not.toBeInTheDocument();
		await expect(canvas.getByRole('status')).toHaveTextContent(READY_ANNOUNCEMENT);
	},
};

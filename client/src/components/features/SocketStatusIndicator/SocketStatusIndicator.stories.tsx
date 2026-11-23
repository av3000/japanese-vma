import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import SocketStatusIndicator from './';

/** The popover renders into `document.body`, outside the canvas. */
const popover = () => within(document.body);

const meta = {
	title: 'Features/Realtime/SocketStatusIndicator',
	component: SocketStatusIndicator,
	parameters: { layout: 'centered' },
} satisfies Meta<typeof SocketStatusIndicator>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Hovering the dot previews the details; leaving closes them again. */
export const Connected: Story = {
	parameters: { socket: 'connected' },
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'WebSocket connected' });
		await userEvent.hover(trigger);

		const details = await popover().findByRole('dialog');
		await expect(within(details).getByText('connected')).toBeInTheDocument();
		await expect(within(details).getByText('ws://localhost:8081')).toBeInTheDocument();
		await expect(within(details).queryByText('Last error')).toBeNull();

		await userEvent.unhover(trigger);
		await waitFor(() => expect(popover().queryByRole('dialog')).toBeNull());
	},
};

/** Keyboard focus previews the details the same way hover does. */
export const Connecting: Story = {
	parameters: { socket: 'connecting' },
	play: async ({ canvasElement }) => {
		await userEvent.tab();
		const trigger = within(canvasElement).getByRole('button', { name: 'WebSocket connecting' });
		await expect(trigger).toHaveFocus();
		await expect(await popover().findByRole('dialog')).toHaveTextContent('connecting');
	},
};

/** A click pins the popover open, so the details can be read or copied after the pointer leaves. */
export const Reconnecting: Story = {
	parameters: { socket: 'reconnecting' },
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'WebSocket reconnecting' });
		await userEvent.click(trigger);
		await expect(await popover().findByRole('dialog')).toHaveTextContent('reconnecting');

		await userEvent.unhover(trigger);
		await expect(popover().getByRole('dialog')).toBeInTheDocument();

		await userEvent.keyboard('{Escape}');
		await waitFor(() => expect(popover().queryByRole('dialog')).toBeNull());
	},
};

/** The failed scenario carries the reason the connection gave up. */
export const Failed: Story = {
	parameters: { socket: 'failed' },
	play: async ({ canvasElement }) => {
		await userEvent.hover(within(canvasElement).getByRole('button', { name: 'WebSocket failed' }));
		const details = await popover().findByRole('dialog');
		await expect(within(details).getByText('Last error')).toBeInTheDocument();
		await expect(within(details).getByText('Connection refused by host')).toBeInTheDocument();
	},
};

export const Disconnected: Story = {
	parameters: {
		socket: { isConfigured: true, hasAttemptedConnection: true, connectionStatus: 'disconnected', lastError: null },
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'WebSocket disconnected' })).toBeVisible();
	},
};

/** Before the first attempt the status reads "initializing", whatever the connection state is. */
export const Initializing: Story = {
	parameters: {
		socket: { isConfigured: true, hasAttemptedConnection: false, connectionStatus: 'connecting', lastError: null },
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'WebSocket initializing' })).toBeVisible();
	},
};

/** An error that arrived while the client was still retrying, over a secure target. */
export const WithLastError: Story = {
	parameters: {
		socket: {
			isConfigured: true,
			hasAttemptedConnection: true,
			connectionStatus: 'reconnecting',
			lastError: 'WebSocket connection to wss://realtime.example.com:443 failed: Unexpected response code: 502',
			connectionInfo: { host: 'realtime.example.com', port: 443, scheme: 'wss', appKey: '' },
		},
	},
	play: async ({ canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'WebSocket reconnecting' }));
		const details = await popover().findByRole('dialog');
		await expect(within(details).getByText('wss://realtime.example.com:443')).toBeInTheDocument();
		await expect(within(details).getByText(/Unexpected response code: 502/)).toBeInTheDocument();
	},
};

/** Without a configured realtime server the indicator renders nothing at all. */
export const Unconfigured: Story = {
	parameters: { socket: 'unconfigured' },
	play: async ({ canvasElement }) => {
		await expect(canvasElement).toBeEmptyDOMElement();
	},
};

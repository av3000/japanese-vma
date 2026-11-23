import type { Meta, StoryObj } from '@storybook/react-vite';
import { AxiosError, AxiosHeaders } from 'axios';
import { expect, within } from 'storybook/test';
import { DetailUnavailable, unavailableMessage } from './DetailUnavailable';

/** A failed request as axios throws it. Only the status is read; the server's own text never shows. */
const httpError = (status: number) =>
	new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
		status,
		statusText: '',
		headers: {},
		config: { headers: new AxiosHeaders() },
		data: { title: 'Access denied', detail: 'Article 4d98c557 is private' },
	});

/** A request that never reached the server. */
const offlineError = () => new AxiosError('Network Error', 'ERR_NETWORK');

const meta = {
	title: 'Shared/DetailUnavailable',
	component: DetailUnavailable,
	parameters: { layout: 'padded' },
	args: {
		message: unavailableMessage(httpError(404), 'article'),
		backTo: '/articles',
		backLabel: 'Back to Articles',
	},
} satisfies Meta<typeof DetailUnavailable>;

export default meta;

type Story = StoryObj<typeof meta>;

const expectReasonAndWayBack = async (canvasElement: HTMLElement, message: string, backLabel: string, to: string) => {
	const canvas = within(canvasElement);

	await expect(canvas.getByRole('heading', { level: 1, name: message })).toBeVisible();
	await expect(canvas.getByRole('link', { name: backLabel })).toHaveAttribute('href', to);
};

/** 404: the record is gone. */
export const NotFound: Story = {
	play: async ({ canvasElement }) => {
		await expectReasonAndWayBack(
			canvasElement,
			"This article doesn't exist. It may have been deleted.",
			'Back to Articles',
			'/articles',
		);
	},
};

/** 403 or 401: the record exists, and the viewer may not read it. */
export const Private: Story = {
	args: {
		message: unavailableMessage(httpError(403), 'catalogue'),
		backTo: '/catalogues',
		backLabel: 'Back to Catalogues',
	},
	play: async ({ canvasElement }) => {
		await expectReasonAndWayBack(canvasElement, 'This catalogue is private.', 'Back to Catalogues', '/catalogues');
	},
};

/** No response at all: offline, or the server is down. */
export const Unreachable: Story = {
	args: { message: unavailableMessage(offlineError(), 'kanji'), backTo: '/kanjis', backLabel: 'Back to Kanji' },
	play: async ({ canvasElement }) => {
		await expectReasonAndWayBack(
			canvasElement,
			"We couldn't reach the server. Check your connection and try again.",
			'Back to Kanji',
			'/kanjis',
		);
	},
};

/** Any other failure, such as a 500. The server's text is never shown. */
export const LoadFailed: Story = {
	args: { message: unavailableMessage(httpError(500), 'word'), backTo: '/words', backLabel: 'Back to Words' },
	play: async ({ canvasElement }) => {
		await expectReasonAndWayBack(
			canvasElement,
			"This word couldn't be loaded. Please try again.",
			'Back to Words',
			'/words',
		);
		await expect(within(canvasElement).queryByText(/4d98c557/)).not.toBeInTheDocument();
	},
};

export const OnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('link', { name: 'Back to Articles' })).toBeVisible();
	},
};

import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, waitFor, within } from 'storybook/test';
import { getPostShowMockHandler } from '@/api/generated/post/post.msw';
import { makePostDetailResource } from '@/components/features/community/fixtures';
import { currentLocation } from '@/storybook/decorators';
import { POST_UUID, failWith } from '../../routeFixtures';
import PostDetails from './';

/*
 * The post route itself: it fetches by the `:post_id` param through the generated show handler and
 * renders the loading state, the unavailable panel or the post. The page layout is covered by
 * `Pages/PostDetails` (`PostContent`). Posts are public, so there is no private state.
 */

const post = makePostDetailResource({ uuid: POST_UUID });

const meta = {
	title: 'Pages/PostDetails/Route states',
	component: PostDetails,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/community/:post_id', initialEntries: [`/community/${POST_UUID}`] },
	},
} satisfies Meta<typeof PostDetails>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getPostShowMockHandler(post));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent(post.title);
	},
};

/** A link with the post's old numeric id: the route loads it, then replaces the address with the UUID. */
export const LegacyNumericId: Story = {
	parameters: { router: { path: '/community/:post_id', initialEntries: ['/community/41'] } },
	beforeEach: ({ msw }) => {
		msw.use(getPostShowMockHandler(post));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent(post.title);
		await waitFor(() => expect(currentLocation()).toBe(`/community/${POST_UUID}`));
	},
};

/** The post request never answers: the route announces the loading state. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getPostShowMockHandler(async () => {
				await delay('infinite');

				return post;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading page.');
	},
};

/** A 404 says the post does not exist and links back to Community. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getPostShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This post doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Community' })).toHaveAttribute('href', '/community');
	},
};

/** A 500 asks the reader to try again, in words that blame neither the post nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getPostShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This post couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};

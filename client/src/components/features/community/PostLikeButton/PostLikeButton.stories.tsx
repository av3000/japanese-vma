import { useState } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useQueryClient } from '@tanstack/react-query';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getLikeLikeInstanceMockHandler } from '@/api/generated/like/like.msw';
import type { LikeInstanceRequest } from '@/api/generated/model/likeInstanceRequest';
import { getPostDetailQueryKey, usePostQuery } from '@/api/posts/reads';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { makePostDetailResource } from '../fixtures';
import PostLikeButton from './';

const POST_ID = 41;
const POST_UUID = 'post-41';

/**
 * Holds the post the way the detail route does: the button reads its count and pressed state from
 * the cached detail, so a like shows up in the label. The args seed that cache once.
 */
const CachedPostLikeButton = (props: React.ComponentProps<typeof PostLikeButton>) => {
	const queryClient = useQueryClient();

	useState(() =>
		queryClient.setQueryData(
			getPostDetailQueryKey(props.detailIdentifier),
			makePostDetailResource({
				id: props.postId,
				engagement: {
					stats: {
						likes_count: String(props.likesCount),
						views_count: '348',
						comments_count: '9',
						downloads_count: '0',
					},
					is_liked_by_viewer: props.isLiked,
				},
			}),
		),
	);

	const { data: post } = usePostQuery(props.detailIdentifier);

	return (
		<PostLikeButton
			{...props}
			likesCount={post?.engagementCounts.likes ?? props.likesCount}
			isLiked={post?.engagement.is_liked_by_viewer ?? props.isLiked}
		/>
	);
};

const meta = {
	title: 'Features/Community/PostLikeButton',
	component: PostLikeButton,
	parameters: { layout: 'padded', auth: { user: users.member } },
	args: { postId: POST_ID, detailIdentifier: POST_UUID, likesCount: 12, isLiked: false },
	render: (args) => <CachedPostLikeButton {...args} />,
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '16rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof PostLikeButton>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated like handler received, newest last; cleared before each story that likes. */
const sent: LikeInstanceRequest[] = [];

const likeRequests = {
	/** Answers every like with the given result and records the body. */
	answeringWith: (result: { is_liked: boolean; likes_count: number }) => {
		sent.length = 0;

		return getLikeLikeInstanceMockHandler(async ({ request }) => {
			sent.push((await request.json()) as LikeInstanceRequest);

			return result;
		});
	},
};

/** A guest is sent to the login page without any request. */
export const Guest: Story = {
	parameters: { auth: 'guest', router: { initialEntries: ['/community/post-41'] } },
	beforeEach: ({ msw }) => {
		msw.use(likeRequests.answeringWith({ is_liked: true, likes_count: 13 }));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(currentLocation()).toBe('/community/post-41');

		await userEvent.click(canvas.getByRole('button', { name: 'Like · 12' }));

		await waitFor(() => expect(currentLocation()).toBe('/login'));
		await expect(sent).toEqual([]);
	},
};

export const NotLiked: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Like · 12' })).toHaveAttribute(
			'aria-pressed',
			'false',
		);
	},
};

/** The viewer's earlier like shows as pressed. A click takes it back, and the served count replaces the old one. */
export const Liked: Story = {
	args: { isLiked: true, likesCount: 13 },
	beforeEach: ({ msw }) => {
		msw.use(likeRequests.answeringWith({ is_liked: false, likes_count: 12 }));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('button', { name: 'Like · 13' })).toHaveAttribute('aria-pressed', 'true');

		await userEvent.click(canvas.getByRole('button', { name: 'Like · 13' }));

		await expect(await canvas.findByRole('button', { name: 'Like · 12' })).toHaveAttribute('aria-pressed', 'false');
		await waitFor(() => expect(sent).toEqual([{ template_id: 9, real_object_id: POST_ID }]));
	},
};

/** A member likes the post: the POST names the post type and id, and the label shows the new count, pressed. */
export const LikesPost: Story = {
	beforeEach: ({ msw }) => {
		msw.use(likeRequests.answeringWith({ is_liked: true, likes_count: 13 }));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Like · 12' }));

		await expect(await canvas.findByRole('button', { name: 'Like · 13' })).toHaveAttribute('aria-pressed', 'true');
		await waitFor(() => expect(sent).toEqual([{ template_id: 9, real_object_id: POST_ID }]));
	},
};

/** The request has not answered: the like already shows (optimistic) and the button is disabled, so it cannot be sent twice. */
export const Pending: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getLikeLikeInstanceMockHandler(async () => {
				await delay('infinite');

				return { is_liked: true, likes_count: 13 };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Like · 12' }));

		const button = await canvas.findByRole('button', { name: 'Like · 13' });
		await expect(button).toBeDisabled();
		await expect(button).toHaveAttribute('aria-pressed', 'true');
	},
};

/** The request fails: the button comes back with the earlier count and state. It shows no message of its own. */
export const LikeError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getLikeLikeInstanceMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Like · 12' }));

		await waitFor(() => expect(canvas.getByRole('button', { name: 'Like · 12' })).toBeEnabled());
		await expect(canvas.getByRole('button', { name: 'Like · 12' })).toHaveAttribute('aria-pressed', 'false');
	},
};

/** A large count is grouped. */
export const BigCount: Story = {
	args: { likesCount: 12408 },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Like · 12,408' })).toBeVisible();
	},
};

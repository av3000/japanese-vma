import type * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { getCommentsQueryKey } from '@/api/comments';
import type { CommentResource } from '@/api/generated/model/commentResource';
import { makeHashtags } from '@/components/features/Homepage/fixtures';
import { LONGEST_POST_TITLE, POST_AUTHOR, makePostDetail } from '@/components/features/community/fixtures';
import { AuthContext } from '@/providers/contexts/auth-provider';
import { SeededQueryClient, type QuerySeed } from '@/test/seededQueryClient';
import PostContent from './';

type AuthValue = NonNullable<React.ComponentProps<typeof AuthContext.Provider>['value']>;

const noop = async () => undefined;
const guest: AuthValue = {
	user: null,
	isAuthenticated: false,
	isLoading: false,
	sessionExpired: false,
	token: null,
	login: noop,
	register: noop,
	logout: noop,
	clearSessionExpired: () => undefined,
};
const signedIn = (id: number, isAdmin = false): AuthValue => ({
	...guest,
	isAuthenticated: true,
	token: 'story-token',
	user: { id, uuid: `user-${id}`, name: 'Story user', email: 'user@example.com', roles: [], isAdmin },
});
const owner = signedIn(POST_AUTHOR.id);
const admin = signedIn(99, true);

const POST_UUID = 'post-41';

const comment = (index: number): CommentResource =>
	({
		id: index,
		uuid: `comment-${index}`,
		entity_uuid: POST_UUID,
		entity_type_uuid: '' as CommentResource['entity_type_uuid'],
		entity_type_label: 'post',
		author: { id: 100 + index, name: `Reader ${index}`, uuid: `reader-${index}` },
		content:
			index === 1 ? 'I think of が as pointing at something new.' : 'は sets the topic, が marks the subject.',
		parent_comment_id: null,
		is_reply: false,
		likes_count: index % 3,
		viewer: { is_liked: false, can_edit: false, can_delete: false },
		replies_count: 0,
		replies: [],
		created_at: '2026-09-29T10:00:00+00:00',
		updated_at: '2026-09-29T10:00:00+00:00',
	}) as CommentResource;

const comments = (count: number, uuid = POST_UUID): QuerySeed => ({
	queryKey: getCommentsQueryKey('post', uuid),
	data: {
		items: Array.from({ length: count }, (_, index) => comment(index + 1)),
		pagination: { page: 1, per_page: 20, total: count, last_page: 1, has_more: false },
	},
});

interface StoryParams {
	auth?: AuthValue;
	seeds?: QuerySeed[];
}

const meta = {
	title: 'Pages/PostDetails',
	component: PostContent,
	tags: ['autodocs'],
	parameters: { layout: 'fullscreen' },
	args: { post: makePostDetail() },
	decorators: [
		(Story, context) => {
			const params = context.parameters as StoryParams;

			return (
				<AuthContext.Provider value={params.auth ?? guest}>
					<SeededQueryClient seeds={params.seeds ?? [comments(2)]}>
						<Story />
					</SeededQueryClient>
				</AuthContext.Provider>
			);
		},
	],
} satisfies Meta<typeof PostContent>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A visitor: the topic, one h1, the byline, the counts, Like and the tags. No owner or admin groups. */
export const Guest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('button', { name: /Like · 12/ })).toBeVisible();
		await expect(canvas.getByRole('link', { name: '#grammar' })).toHaveAttribute(
			'href',
			'/community?hashtag=grammar',
		);
		await expect(canvas.queryByRole('region', { name: 'Your post' })).not.toBeInTheDocument();
		await expect(canvas.queryByRole('region', { name: 'Moderation' })).not.toBeInTheDocument();
	},
};

/** The author: Edit and Delete under "Your post". */
export const Owner: Story = {
	parameters: { auth: owner },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('region', { name: 'Your post' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Edit post' })).toHaveAttribute(
			'href',
			'/community/edit/post-41',
		);
		await expect(canvas.queryByRole('region', { name: 'Moderation' })).not.toBeInTheDocument();
	},
};

/** An admin who did not write the post: Lock and Delete under "Moderation". */
export const Admin: Story = {
	parameters: { auth: admin },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('region', { name: 'Moderation' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Lock post' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Delete post' })).toBeVisible();
	},
};

/** Locked: the Locked pill in the header, Unlock for the admin, the comment form replaced by a notice. */
export const LockedForAdmin: Story = {
	args: { post: makePostDetail({ locked: true, topic: 7, topic_label: 'Announcement' }) },
	parameters: { auth: admin },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Locked')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Unlock post' })).toBeVisible();
		await expect(canvas.getByText('This post is locked and new comments are not allowed.')).toBeVisible();
	},
};

/** 255 characters with no spaces, a long body with an unbroken run, five tags and zero of everything. */
export const LongestTitleZeroComments: Story = {
	args: {
		post: makePostDetail({
			title: LONGEST_POST_TITLE,
			topic: 5,
			topic_label: 'Bug',
			content: [
				'Steps to reproduce on a 360px phone:',
				'1. Open the search drawer.\n2. Paste this URL: https://example.com/' + 'a'.repeat(300),
				'Expected: it wraps. Actual: '.concat('長'.repeat(400)),
			].join('\n\n'),
			hashtags: makeHashtags(['bug', 'search', 'mobile', 'safari', 'layout-overflow-on-narrow-screens']),
			engagement: { stats: null },
		}),
	},
	parameters: { seeds: [comments(0)] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1 })).toHaveTextContent(LONGEST_POST_TITLE);
		await expect(canvas.getByRole('button', { name: /Like · 0/ })).toBeVisible();
	},
};

/** A Japanese title and body are marked `lang="ja"`. */
export const Japanese: Story = {
	args: {
		post: makePostDetail({
			title: '日本語能力試験N3の勉強方法について',
			content: '毎日少しずつ漢字を覚えています。\nおすすめの教材はありますか？',
			author: { id: 9, uuid: 'author-9', name: '山田太郎' },
		}),
	},
};

import type * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import type { PostListItemResource } from '@/api/generated/model';
import { getInfinitePostsQueryKey, parsePostListFilters } from '@/api/posts/reads';
import { makeHashtags } from '@/components/features/Homepage/fixtures';
import { LONGEST_POST_TITLE, makePostListItemResource } from '@/components/features/community/fixtures';
import { AuthContext } from '@/providers/contexts/auth-provider';
import { infiniteSeed, SeededQueryClient, type QuerySeed } from '@/test/seededQueryClient';
import PostsList from './';

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
const signedIn: AuthValue = {
	...guest,
	isAuthenticated: true,
	token: 'story-token',
	user: { id: 7, uuid: 'user-7', name: 'Hanako Sato', email: 'hanako@example.com', roles: [], isAdmin: false },
};

// The story iframe's own `?id=` parameters are not post filters, so this is the unfiltered key.
const listKey = getInfinitePostsQueryKey(parsePostListFilters(new URLSearchParams()));

const firstPage = (items: PostListItemResource[], total = items.length): QuerySeed =>
	infiniteSeed(listKey, [
		{
			items,
			pagination: {
				page: 1,
				per_page: 10,
				total,
				last_page: Math.ceil(total / 10) || 1,
				has_more: total > items.length,
			},
		},
	]);

const posts: PostListItemResource[] = [
	makePostListItemResource(),
	makePostListItemResource({
		id: 42,
		uuid: 'post-42',
		title: 'Site maintenance this weekend',
		topic: 7,
		topic_label: 'Announcement',
		locked: true,
		hashtags: makeHashtags(['news']),
	}),
	makePostListItemResource({
		id: 43,
		uuid: 'post-43',
		title: LONGEST_POST_TITLE,
		topic: 5,
		topic_label: 'Bug',
		author: { id: 8, uuid: 'author-8', name: 'A very long display name that keeps going past the card' },
		hashtags: makeHashtags(['bug', 'search', 'mobile', 'safari', 'layout']),
		engagement: {
			stats: { likes_count: '12408', views_count: '9999999', comments_count: '96', downloads_count: '0' },
		},
	}),
	makePostListItemResource({
		id: 44,
		uuid: 'post-44',
		title: '日本語能力試験N3の勉強方法について',
		topic: 1,
		topic_label: 'Content-related',
		author: { id: 9, uuid: 'author-9', name: '山田太郎' },
	}),
	makePostListItemResource({
		id: 45,
		uuid: 'post-45',
		title: 'First post',
		topic: 2,
		topic_label: 'Off-topic',
		hashtags: [],
		engagement: { stats: null },
	}),
];

interface StoryParams {
	auth?: AuthValue;
	seeds?: QuerySeed[];
}

const meta = {
	title: 'Pages/PostsList',
	component: PostsList,
	parameters: { layout: 'fullscreen' },
	decorators: [
		(Story, context) => {
			const params = context.parameters as StoryParams;

			return (
				<AuthContext.Provider value={params.auth ?? guest}>
					<SeededQueryClient seeds={params.seeds ?? [firstPage(posts, 23)]}>
						<Story />
					</SeededQueryClient>
				</AuthContext.Provider>
			);
		},
	],
} satisfies Meta<typeof PostsList>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Five posts of 23: a locked announcement, the longest title, a Japanese title and one with zero of everything. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1, name: 'Community' })).toBeVisible();
		await expect(canvas.getByText('Showing 5 of 23')).toBeVisible();
		await expect(canvas.getAllByRole('article')).toHaveLength(5);
		await expect(canvas.getByText('Locked')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Load More' })).toBeVisible();
	},
};

/** Signed in: "New post" beside the title. */
export const SignedIn: Story = {
	parameters: { auth: signedIn },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('link', { name: 'New post' })).toHaveAttribute('href', '/newpost');
	},
};

/** No posts at all: the empty state, and no "No more results" line under it. */
export const Empty: Story = {
	parameters: { seeds: [firstPage([])] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('No posts yet')).toBeVisible();
		await expect(canvas.queryByText('No more results')).not.toBeInTheDocument();
	},
};

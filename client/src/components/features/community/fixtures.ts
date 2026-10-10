/** Typed fixtures for the Community stories and tests. Not imported by application code. */
import type { PostDetailResource, PostListItemResource } from '@/api/generated/model';
import { mapPostDetail, mapPostListItem, type MappedPostDetail, type MappedPostListItem } from '@/api/posts/reads';
import { makeHashtags } from '@/components/features/Homepage/fixtures';

export const POST_AUTHOR = { id: 7, name: 'Hanako Sato', uuid: 'author-uuid' };

/** 255 characters with no spaces: the longest title the backend accepts, and no break points. */
export const LONGEST_POST_TITLE = 'Why-does-the-particle-は-change-the-meaning-of-every-sentence-'
	.repeat(5)
	.slice(0, 255);

const engagement = (likes: number, views: number, comments: number) => ({
	stats: {
		likes_count: String(likes),
		views_count: String(views),
		comments_count: String(comments),
		downloads_count: '0',
	},
});

export const makePostListItemResource = (overrides: Partial<PostListItemResource> = {}): PostListItemResource => ({
	id: 41,
	uuid: 'post-41',
	entity_type_uuid: 'post-type-uuid',
	title: 'How do you remember the difference between は and が?',
	topic: 3,
	topic_label: 'FAQ',
	locked: false,
	author: POST_AUTHOR,
	hashtags: makeHashtags(['grammar', 'particles']),
	engagement: engagement(12, 348, 9),
	created_at: '2026-09-28T09:30:00Z',
	updated_at: '2026-09-28T09:30:00Z',
	...overrides,
});

export const makePostListItem = (overrides: Partial<PostListItemResource> = {}): MappedPostListItem =>
	mapPostListItem(makePostListItemResource(overrides));

export const makePostDetailResource = (overrides: Partial<PostDetailResource> = {}): PostDetailResource => ({
	...makePostListItemResource(),
	engagement: { ...engagement(12, 348, 9), is_liked_by_viewer: false },
	content:
		'I keep mixing them up when I write.\nIs there a rule of thumb that works for you?\n\nExample: 私は学生です vs 私が学生です.',
	...overrides,
});

export const makePostDetail = (overrides: Partial<PostDetailResource> = {}): MappedPostDetail =>
	mapPostDetail(makePostDetailResource(overrides));

/** Hostile-data posts for the Community list (#524). */
export const communityPosts = {
	default: makePostListItem(),
	locked: makePostListItem({
		id: 42,
		uuid: 'post-42',
		title: 'Site maintenance this weekend',
		topic: 7,
		topic_label: 'Announcement',
		locked: true,
		hashtags: makeHashtags(['news']),
		engagement: engagement(31, 1204, 0),
	}),
	longest: makePostListItem({
		id: 43,
		uuid: 'post-43',
		title: LONGEST_POST_TITLE,
		topic: 5,
		topic_label: 'Bug',
		author: { id: 8, uuid: 'author-8', name: 'A very long display name that keeps going past the card' },
		hashtags: makeHashtags(['bug', 'search', 'mobile', 'safari', 'layout']),
		engagement: engagement(12408, 9_999_999, 96),
	}),
	japanese: makePostListItem({
		id: 44,
		uuid: 'post-44',
		title: '日本語能力試験N3の勉強方法について',
		topic: 1,
		topic_label: 'Content-related',
		author: { id: 9, uuid: 'author-9', name: '山田太郎' },
	}),
	empty: makePostListItem({
		id: 45,
		uuid: 'post-45',
		title: 'First post',
		topic: 2,
		topic_label: 'Off-topic',
		hashtags: [],
		engagement: { stats: null },
	}),
} satisfies Record<string, MappedPostListItem>;

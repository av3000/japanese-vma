import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentReplyListResource } from '@/api/generated/model/commentReplyListResource';
import type { CommentReplyResource } from '@/api/generated/model/commentReplyResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import type { CommentViewerResource } from '@/api/generated/model/commentViewerResource';

/** The article every comment fixture belongs to. */
export const COMMENTED_ARTICLE = { id: 321, uuid: '8b0c1d2e-3f40-4a51-8b62-7c83d94ea5f6' };

/** A top-level comment by reader `index`; the viewer may not edit or delete it. */
export const makeComment = (index: number, overrides: Partial<CommentResource> = {}): CommentResource =>
	({
		id: index,
		uuid: `comment-${index}`,
		entity_uuid: COMMENTED_ARTICLE.uuid,
		entity_type_uuid: '' as CommentResource['entity_type_uuid'],
		entity_type_label: 'article',
		author: { id: 100 + index, name: `Reader ${index}`, uuid: `reader-${index}` },
		content: 'とても分かりやすい記事でした。ありがとうございます。',
		parent_comment_id: null,
		is_reply: false,
		likes_count: index % 3,
		viewer: { is_liked: false, can_edit: false, can_delete: false },
		replies_count: 0,
		replies: [],
		created_at: '2026-05-26T10:00:00+00:00',
		updated_at: '2026-05-26T10:00:00+00:00',
		...overrides,
	}) as CommentResource;

/** One page of a comment thread, with the pagination the API sends. */
export const threadOf = (items: CommentResource[], total = items.length): CommentListResource => ({
	items,
	pagination: { page: 1, per_page: 20, total, last_page: Math.ceil(total / 20) || 1, has_more: total > items.length },
});

/** What the API says about the viewer's own comment: they may edit and delete it. */
export const OWN_VIEWER: CommentViewerResource = { is_liked: false, can_edit: true, can_delete: true };

/** The `author` of a comment written by a story viewer from `@/storybook/fixtures/users`. */
export const authorOf = (user: { id: number; name: string; uuid: string }) => ({
	id: user.id,
	name: user.name,
	uuid: user.uuid,
});

/** A reply by reader `index` to the comment `parentId`. */
export const makeReply = (
	index: number,
	parentId: number,
	overrides: Partial<CommentReplyResource> = {},
): CommentReplyResource => ({
	id: index,
	uuid: `comment-${index}`,
	entity_uuid: COMMENTED_ARTICLE.uuid,
	entity_type_uuid: '' as CommentReplyResource['entity_type_uuid'],
	entity_type_label: 'article',
	author: { id: 100 + index, name: `Reader ${index}`, uuid: `reader-${index}` },
	content: '私もそう思います。例文が特に役に立ちました。',
	parent_comment_id: parentId,
	is_reply: true,
	likes_count: 0,
	viewer: { is_liked: false, can_edit: false, can_delete: false },
	created_at: '2026-05-26T11:00:00+00:00',
	updated_at: '2026-05-26T11:00:00+00:00',
	...overrides,
});

/** A top-level comment with `replies` loaded and `repliesCount` replies in all (at least `replies.length`). */
export const makeThreadComment = (
	index: number,
	replies: CommentReplyResource[],
	repliesCount = replies.length,
	overrides: Partial<CommentResource> = {},
): CommentResource => makeComment(index, { replies, replies_count: repliesCount, ...overrides });

/** One page of the replies endpoint. */
export const repliesOf = (items: CommentReplyResource[]): CommentReplyListResource => ({
	items,
	pagination: { page: 1, per_page: 20, total: items.length, last_page: 1, has_more: false },
});

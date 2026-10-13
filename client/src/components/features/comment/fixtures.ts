import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentResource } from '@/api/generated/model/commentResource';

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

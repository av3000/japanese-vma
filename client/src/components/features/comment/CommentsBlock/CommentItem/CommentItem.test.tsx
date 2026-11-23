import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import type { ApiComment } from '@/api/comments';
import CommentItem from './CommentItem';

const comment = (overrides: Partial<ApiComment> = {}): ApiComment =>
	({
		id: 1,
		uuid: 'comment-1',
		entity_uuid: 'post-41',
		entity_type_uuid: '',
		entity_type_label: 'post',
		author: { id: 101, name: 'Reader', uuid: 'reader-1' },
		content: 'は sets the topic.',
		parent_comment_id: null,
		is_reply: false,
		likes_count: 0,
		viewer: { is_liked: false, can_edit: false, can_delete: false },
		replies_count: 0,
		replies: [],
		created_at: '2025-09-29T10:00:00+00:00',
		updated_at: '2025-09-29T10:00:00+00:00',
		...overrides,
	}) as ApiComment;

const noop = () => undefined;
const idle = { isLikePending: false, isEditPending: false, isDeletePending: false, isReplyPending: false };

const render = (overrides: Partial<ApiComment> = {}) =>
	renderToStaticMarkup(
		<CommentItem
			comment={comment(overrides)}
			canReply={false}
			state={idle}
			onLike={noop}
			onDelete={noop}
			onEdit={async () => undefined}
		/>,
	);

describe('CommentItem', () => {
	it('shows a formatted date with the raw timestamp as its machine-readable value', () => {
		const html = render();

		expect(html).toMatch(/<time[^>]*dateTime="2025-09-29T10:00:00\+00:00"[^>]*>Sep 29, 2025<\/time>/);
		expect(html).not.toContain('>2025-09-29T10:00:00+00:00<');
	});
});

import { beforeEach, describe, expect, it, vi } from 'vitest';
import { commentStore } from '@/api/generated/comment/comment';
import axios from '@/services/axios';
import { addComment, fetchComments } from './comments';

vi.mock('@/api/generated/comment/comment', () => ({
	commentStore: vi.fn(),
}));

vi.mock('@/services/axios', () => ({
	default: {
		get: vi.fn(),
	},
}));

describe('comments api', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('posts comments to the generic v1 comment endpoint for articles', async () => {
		const createdComment = {
			id: 1,
			entity_uuid: '0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
			entity_type: 'article',
			author_name: 'Alana',
			author_id: 7,
			content: 'hello',
			parent_comment_id: null,
			is_reply: false,
			created_at: '2026-05-04T10:00:00Z',
			updated_at: '2026-05-04T10:00:00Z',
			likes_count: 0,
			is_liked_by_viewer: false,
			replies: [],
		};
		vi.mocked(commentStore).mockResolvedValue(createdComment);

		const result = await addComment('ad69baf6-1a1f-42bd-8176-74ab5fbd69bd', 101, '0fb383ad-e203-43f3-9c15-a34bd1ad1a46', {
			content: 'hello',
		});

		expect(result).toBe(createdComment);
		expect(commentStore).toHaveBeenCalledWith({
			entity_type: 'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd',
			entity_id: 101,
			entity_uuid: '0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
			content: 'hello',
		});
	});

	it('posts comments to the generic v1 comment endpoint for catalogues', async () => {
		const createdComment = {
			id: 2,
			entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
			entity_type: 'list',
			author_name: 'Alana',
			author_id: 7,
			content: 'hi',
			parent_comment_id: null,
			is_reply: false,
			created_at: '2026-05-04T10:00:00Z',
			updated_at: '2026-05-04T10:00:00Z',
			likes_count: 0,
			is_liked_by_viewer: false,
			replies: [],
		};
		vi.mocked(commentStore).mockResolvedValue(createdComment);

		const result = await addComment('93edeaab-85d0-44ad-ba2d-4602ab4061ba', 202, '57b661a6-85c5-4369-bb08-3896cc03e853', {
			content: 'hi',
		});

		expect(result).toBe(createdComment);
		expect(commentStore).toHaveBeenCalledWith({
			entity_type: '93edeaab-85d0-44ad-ba2d-4602ab4061ba',
			entity_id: 202,
			entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
			content: 'hi',
		});
	});

	it('fetches direct paginated comment resources without a data wrapper', async () => {
		const commentsResponse = {
			items: [
				{
					id: 3,
					entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
					entity_type: 'list',
					author_name: 'Alana',
					author_id: 7,
					content: 'direct list comment',
					parent_comment_id: null,
					is_reply: false,
					created_at: '2026-05-04T10:00:00Z',
					updated_at: '2026-05-04T10:00:00Z',
					likes_count: 0,
					is_liked_by_viewer: false,
					replies: [],
				},
			],
			pagination: {
				page: 1,
				per_page: 20,
				total: 1,
				last_page: 1,
				has_more: false,
			},
		};
		vi.mocked(axios.get).mockResolvedValue({ data: commentsResponse });

		await expect(
			fetchComments('catalogue', '57b661a6-85c5-4369-bb08-3896cc03e853', { include_likes: true }),
		).resolves.toBe(commentsResponse);
		expect(axios.get).toHaveBeenCalledWith('v1/catalogues/57b661a6-85c5-4369-bb08-3896cc03e853/comments', {
			params: { include_likes: true },
		});
	});
});

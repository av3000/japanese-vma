import { beforeEach, describe, expect, it, vi } from 'vitest';
import { commentStore } from '@/api/generated/comment/comment';
import { addComment } from './comments';

vi.mock('@/api/generated/comment/comment', () => ({
	commentStore: vi.fn(),
}));

describe('comments api', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('posts comments to the generic v1 comment endpoint for articles', async () => {
		vi.mocked(commentStore).mockResolvedValue({ data: { id: 1 } } as never);

		await addComment('ad69baf6-1a1f-42bd-8176-74ab5fbd69bd', 101, '0fb383ad-e203-43f3-9c15-a34bd1ad1a46', {
			content: 'hello',
		});

		expect(commentStore).toHaveBeenCalledWith({
			entity_type: 'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd',
			entity_id: 101,
			entity_uuid: '0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
			content: 'hello',
		});
	});

	it('posts comments to the generic v1 comment endpoint for catalogues', async () => {
		vi.mocked(commentStore).mockResolvedValue({ data: { id: 2 } } as never);

		await addComment('93edeaab-85d0-44ad-ba2d-4602ab4061ba', 202, '57b661a6-85c5-4369-bb08-3896cc03e853', {
			content: 'hi',
		});

		expect(commentStore).toHaveBeenCalledWith({
			entity_type: '93edeaab-85d0-44ad-ba2d-4602ab4061ba',
			entity_id: 202,
			entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
			content: 'hi',
		});
	});
});

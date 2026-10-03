import { QueryClient } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';
import {
	applyPostLockToCaches,
	canDeletePost,
	canLockPost,
	canUpdatePost,
	evictPostCaches,
	nextLockRequest,
	postDetailQueryKeys,
	reconcilePostCaches,
	type PostWriteResponse,
} from './writes';

const createPost = (overrides: Partial<PostWriteResponse> = {}): PostWriteResponse => ({
	id: 12,
	uuid: 'post-uuid',
	entity_type_uuid: 'entity-type-uuid',
	title: 'How do I read this kanji?',
	topic: 1,
	topic_label: 'Content-related',
	locked: false,
	content: 'The second character keeps throwing me.',
	author: { id: 5, name: 'Author' } as PostWriteResponse['author'],
	hashtags: [],
	engagement: {} as PostWriteResponse['engagement'],
	created_at: '2026-01-01T00:00:00Z',
	updated_at: '2026-01-01T00:00:00Z',
	...overrides,
});

const AUTHOR_ID = 5;

const owner = { id: AUTHOR_ID, isAdmin: false };
const admin = { id: 9, isAdmin: true };
const stranger = { id: 42, isAdmin: false };

describe('post write gates', () => {
	it('allows only the owner to update, matching PostPolicy::canUpdate', () => {
		expect(canUpdatePost(owner, AUTHOR_ID)).toBe(true);
		// An admin moderates by locking or deleting, never by rewriting another author's words.
		expect(canUpdatePost(admin, AUTHOR_ID)).toBe(false);
		expect(canUpdatePost(stranger, AUTHOR_ID)).toBe(false);
		expect(canUpdatePost(null, AUTHOR_ID)).toBe(false);
	});

	it('allows the owner or an admin to delete, matching PostPolicy::canDelete', () => {
		expect(canDeletePost(owner, AUTHOR_ID)).toBe(true);
		expect(canDeletePost(admin, AUTHOR_ID)).toBe(true);
		expect(canDeletePost(stranger, AUTHOR_ID)).toBe(false);
		expect(canDeletePost(null, AUTHOR_ID)).toBe(false);
	});

	it('allows only an admin to lock, matching PostPolicy::canLock', () => {
		expect(canLockPost(admin)).toBe(true);
		expect(canLockPost(owner)).toBe(false);
		expect(canLockPost(null)).toBe(false);
		expect(canLockPost(undefined)).toBe(false);
	});
});

describe('postDetailQueryKeys', () => {
	it('covers both the UUID and the transitional numeric detail identifier', () => {
		expect(postDetailQueryKeys(createPost())).toEqual([['/posts/post-uuid'], ['/posts/12']]);
	});
});

describe('reconcilePostCaches', () => {
	it('writes the response into both detail entries and invalidates the lists', () => {
		const queryClient = new QueryClient();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries').mockReturnValue(Promise.resolve());

		queryClient.setQueryData(['/posts/post-uuid'], createPost({ title: 'stale by uuid' }));
		queryClient.setQueryData(['/posts/12'], createPost({ title: 'stale by id' }));

		const updated = createPost({ title: 'Answered, thanks!' });
		reconcilePostCaches(queryClient, updated);

		expect(queryClient.getQueryData(['/posts/post-uuid'])).toEqual(updated);
		expect(queryClient.getQueryData(['/posts/12'])).toEqual(updated);
		expect(invalidate).toHaveBeenCalledWith({ queryKey: ['/posts'] });
	});
});

describe('evictPostCaches', () => {
	it('removes both detail entries and invalidates the lists', () => {
		const queryClient = new QueryClient();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries').mockReturnValue(Promise.resolve());

		queryClient.setQueryData(['/posts/post-uuid'], createPost());
		queryClient.setQueryData(['/posts/12'], createPost());

		evictPostCaches(queryClient, createPost());

		expect(queryClient.getQueryData(['/posts/post-uuid'])).toBeUndefined();
		expect(queryClient.getQueryData(['/posts/12'])).toBeUndefined();
		expect(invalidate).toHaveBeenCalledWith({ queryKey: ['/posts'] });
	});
});

describe('applyPostLockToCaches', () => {
	it('patches the lock state into both cached details without discarding the rest of the Post', () => {
		const queryClient = new QueryClient();
		vi.spyOn(queryClient, 'invalidateQueries').mockReturnValue(Promise.resolve());

		queryClient.setQueryData(['/posts/post-uuid'], createPost());
		queryClient.setQueryData(['/posts/12'], createPost());

		applyPostLockToCaches(queryClient, createPost(), { uuid: 'post-uuid', locked: true });

		expect(queryClient.getQueryData(['/posts/post-uuid'])).toMatchObject({
			locked: true,
			title: 'How do I read this kanji?',
		});
		expect(queryClient.getQueryData(['/posts/12'])).toMatchObject({ locked: true });
	});

	it('leaves an unseeded cache entry alone rather than inventing a partial Post', () => {
		const queryClient = new QueryClient();
		vi.spyOn(queryClient, 'invalidateQueries').mockReturnValue(Promise.resolve());

		applyPostLockToCaches(queryClient, createPost(), { uuid: 'post-uuid', locked: true });

		expect(queryClient.getQueryData(['/posts/post-uuid'])).toBeUndefined();
	});
});

describe('nextLockRequest', () => {
	it('asks for the opposite state explicitly, so a retry cannot flip the Post back', () => {
		expect(nextLockRequest(false)).toEqual({ locked: true });
		expect(nextLockRequest(true)).toEqual({ locked: false });
	});
});

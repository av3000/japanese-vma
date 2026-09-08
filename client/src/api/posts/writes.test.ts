import { QueryClient } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';
import {
	applyPostLockToCaches,
	canDeletePost,
	canLockPost,
	canUpdatePost,
	evictPostCaches,
	GENERIC_POST_WRITE_ERROR,
	nextLockRequest,
	postDetailQueryKeys,
	readPostWriteError,
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

const axiosError = (status: number, data: Record<string, unknown>) => ({ response: { data: { status, ...data } } });

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

describe('readPostWriteError', () => {
	it('surfaces field errors from a validation payload', () => {
		const failure = readPostWriteError(
			axiosError(422, { title: 'Validation failed', errors: { title: ['Title is too short.'] } }),
		);

		expect(failure).toEqual({
			kind: 'validation',
			message: 'Validation failed',
			errors: { title: ['Title is too short.'] },
		});
	});

	it('distinguishes the problem-details failures the write endpoints return', () => {
		expect(readPostWriteError(axiosError(403, { title: 'This post is not yours.' }))).toEqual({
			kind: 'forbidden',
			message: 'This post is not yours.',
		});
		expect(readPostWriteError(axiosError(404, { title: 'Not found.' })).kind).toBe('notFound');
		expect(readPostWriteError(axiosError(401, { title: 'Unauthenticated.' })).kind).toBe('unauthenticated');
	});

	it('falls back to a generic message for network and unrecognised failures', () => {
		expect(readPostWriteError(new Error('Network Error'))).toEqual({
			kind: 'unknown',
			message: GENERIC_POST_WRITE_ERROR,
		});
		expect(readPostWriteError(axiosError(500, { title: 'Post update failed.' })).message).toBe(
			GENERIC_POST_WRITE_ERROR,
		);
	});
});

import { useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import type { LockPostRequest } from '@/api/generated/model/lockPostRequest';
import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import type { PostLockResource } from '@/api/generated/model/postLockResource';
import type { StorePostRequest } from '@/api/generated/model/storePostRequest';
import type { UpdatePostRequest } from '@/api/generated/model/updatePostRequest';
import { getPostIndexQueryKey, postDestroy, postLock, postStore, postUpdate } from '@/api/generated/post/post';
import { getPostDetailQueryKey } from '@/api/posts/reads';
import { readWriteFailure, type WriteFailure } from '@/api/writeFailure';
import type { User } from '@/types';

export type PostWriteResponse = PostDetailResource;

/** Every write addresses the Post by UUID; the numeric id only exists to reconcile legacy caches. */
export type PostIdentity = Pick<PostWriteResponse, 'id' | 'uuid'>;

type PostViewer = Pick<User, 'id' | 'isAdmin'> | null | undefined;

export const GENERIC_POST_WRITE_ERROR = 'Something went wrong. Please try again.';

/**
 * Mirrors `PostPolicy::canUpdate` — deliberately owner-only. An admin moderates a Post by locking
 * or deleting it, never by rewriting another author's words, so admin is *not* an escape hatch here.
 */
export const canUpdatePost = (viewer: PostViewer, authorId: number): boolean => viewer?.id === authorId;

/** Mirrors `PostPolicy::canDelete` — owner or admin. */
export const canDeletePost = (viewer: PostViewer, authorId: number): boolean =>
	!!viewer && (viewer.isAdmin || viewer.id === authorId);

/** Mirrors `PostPolicy::canLock` — admin only. */
export const canLockPost = (viewer: PostViewer): boolean => viewer?.isAdmin === true;

/**
 * The detail route resolves both UUIDs and transitional numeric ids, and the generated key is built
 * from whichever identifier the URL carried. A reader who arrived at `/community/12` has their
 * detail cached under `['/posts/12']`, so a write has to reconcile both entries or the page keeps
 * rendering pre-write content.
 */
export const postDetailQueryKeys = (post: PostIdentity) =>
	[getPostDetailQueryKey(post.uuid), getPostDetailQueryKey(String(post.id))] as const;

export const reconcilePostCaches = (queryClient: QueryClient, post: PostWriteResponse): void => {
	for (const queryKey of postDetailQueryKeys(post)) {
		queryClient.setQueryData(queryKey, post);
	}

	queryClient.invalidateQueries({ queryKey: getPostIndexQueryKey() });
};

export const evictPostCaches = (queryClient: QueryClient, post: PostIdentity): void => {
	for (const queryKey of postDetailQueryKeys(post)) {
		queryClient.removeQueries({ queryKey });
	}

	queryClient.invalidateQueries({ queryKey: getPostIndexQueryKey() });
};

/**
 * Lock answers with `{ uuid, locked }` rather than the whole Post, so the cached detail is patched
 * in place. Refetching instead would cost a round trip and, for an authenticated reader, record an
 * extra view for an action that changed one boolean.
 */
export const applyPostLockToCaches = (queryClient: QueryClient, post: PostIdentity, lock: PostLockResource): void => {
	for (const queryKey of postDetailQueryKeys(post)) {
		queryClient.setQueryData(queryKey, (cached: PostWriteResponse | undefined) =>
			cached ? { ...cached, locked: lock.locked } : cached,
		);
	}

	queryClient.invalidateQueries({ queryKey: getPostIndexQueryKey() });
};

export type PostWriteFailure = WriteFailure;

export const readPostWriteError = (error: unknown): PostWriteFailure =>
	readWriteFailure(error, GENERIC_POST_WRITE_ERROR);

export const useCreatePostMutation = () => {
	const queryClient = useQueryClient();

	return useMutation<PostWriteResponse, unknown, StorePostRequest>({
		mutationFn: (payload) => postStore(payload),
		onSuccess: (post) => reconcilePostCaches(queryClient, post),
	});
};

export const useUpdatePostMutation = (uuid: string) => {
	const queryClient = useQueryClient();

	return useMutation<PostWriteResponse, unknown, UpdatePostRequest>({
		mutationFn: (payload) => postUpdate(uuid, payload),
		onSuccess: (post) => reconcilePostCaches(queryClient, post),
	});
};

export const useDeletePostMutation = (post: PostIdentity) => {
	const queryClient = useQueryClient();

	// Type arguments are left to inference: an explicit `void` type argument trips
	// @typescript-eslint/no-invalid-void-type.
	return useMutation({
		// The delete body is intentionally discarded; the endpoint answers 204.
		mutationFn: async (): Promise<void> => {
			await postDestroy(post.uuid);
		},
		onSuccess: () => evictPostCaches(queryClient, post),
	});
};

/**
 * The control renders from the Post's current state and asks for the opposite. Building the body
 * here rather than in the click handler is what keeps the request a desired state instead of the
 * legacy toggle, where a retry after a timeout flipped the Post back.
 */
export const nextLockRequest = (isLocked: boolean): LockPostRequest => ({ locked: !isLocked });

/**
 * The v1 endpoint takes the desired state instead of the legacy toggle, so a retry after a timeout
 * cannot flip the Post back the way `POST /api/post/{id}/toggleLock` could.
 */
export const useLockPostMutation = (post: PostIdentity) => {
	const queryClient = useQueryClient();

	return useMutation<PostLockResource, unknown, LockPostRequest>({
		mutationFn: (payload) => postLock(post.uuid, payload),
		onSuccess: (lock) => applyPostLockToCaches(queryClient, post, lock),
	});
};

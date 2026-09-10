import { useMutation, useQuery, useQueryClient, type QueryKey } from '@tanstack/react-query';
import {
	commentDestroy,
	commentGetArticleComments,
	commentGetCatalogueComments,
	commentGetPostComments,
	commentGetSentenceComments,
	commentReplies,
	commentStore,
	commentUpdate,
	getCommentGetArticleCommentsQueryKey,
	getCommentGetCatalogueCommentsQueryKey,
	getCommentGetPostCommentsQueryKey,
	getCommentGetSentenceCommentsQueryKey,
	getCommentRepliesQueryKey,
} from '@/api/generated/comment/comment';
import type { CommentGetArticleCommentsParams } from '@/api/generated/model/commentGetArticleCommentsParams';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentReplyResource } from '@/api/generated/model/commentReplyResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import { useToggleLikeMutation, type LikeCacheBinding } from '@/api/likes/likes';
import { readWriteFailure, type WriteFailure } from '@/api/writeFailure';
import { ObjectTemplateType } from '@/shared/constants/enums';

export type ApiComment = CommentResource;
export type ApiCommentReply = CommentReplyResource;

/**
 * Every parent's read client takes the same parameters. Orval emits four
 * nominally distinct but structurally identical params types, so one alias
 * stands in for all of them; the `satisfies` on the map below is what proves
 * they still match, and breaks loudly at the map rather than silently at a call
 * site if the backend ever diverges one parent.
 */
export type CommentListParams = CommentGetArticleCommentsParams;

interface CommentParentBinding {
	template: ObjectTemplateType;
	fetchThread: (uuid: string, params?: CommentListParams) => Promise<CommentListResource>;
	buildQueryKey: (uuid: string, params?: CommentListParams) => QueryKey;
}

/**
 * The only place a comment parent is named.
 *
 * The previous seam built its URL as `v1/${objectType}s/${objectId}/comments`,
 * so the route depended on an English pluralization rule holding for every
 * parent. Routing through the generated client per parent removes the concept.
 */
export const COMMENT_PARENTS = {
	article: {
		template: ObjectTemplateType.ARTICLE,
		fetchThread: commentGetArticleComments,
		buildQueryKey: getCommentGetArticleCommentsQueryKey,
	},
	catalogue: {
		template: ObjectTemplateType.LIST,
		fetchThread: commentGetCatalogueComments,
		buildQueryKey: getCommentGetCatalogueCommentsQueryKey,
	},
	post: {
		template: ObjectTemplateType.POST,
		fetchThread: commentGetPostComments,
		buildQueryKey: getCommentGetPostCommentsQueryKey,
	},
	sentence: {
		template: ObjectTemplateType.SENTENCE,
		fetchThread: commentGetSentenceComments,
		buildQueryKey: getCommentGetSentenceCommentsQueryKey,
	},
} as const satisfies Record<string, CommentParentBinding>;

export type CommentParent = keyof typeof COMMENT_PARENTS;

export const COMMENT_PAGE_SIZE = 20;

/** Matches the backend default. Larger threads load the rest through `useCommentRepliesQuery`. */
export const COMMENT_REPLIES_LIMIT = 3;

export const COMMENT_LIST_PARAMS: CommentListParams = {
	per_page: COMMENT_PAGE_SIZE,
	include_replies: true,
	replies_limit: COMMENT_REPLIES_LIMIT,
};

/**
 * Single source for the comment thread cache key.
 *
 * Delegates to the generated key helper so the key cannot drift from the
 * transport, and is addressed by uuid because that is what the request uses.
 * The thread query, every mutation and the like toggle all reconcile the same
 * entry, so none of them may build their own.
 */
export const getCommentsQueryKey = (parent: CommentParent, entityUuid: string): QueryKey =>
	COMMENT_PARENTS[parent].buildQueryKey(entityUuid, COMMENT_LIST_PARAMS);

export const getCommentRepliesCacheKey = (commentUuid: string): QueryKey =>
	getCommentRepliesQueryKey(commentUuid, { per_page: COMMENT_PAGE_SIZE });

export const GENERIC_COMMENT_WRITE_ERROR = 'Something went wrong. Please try again.';

export const readCommentWriteError = (error: unknown): WriteFailure =>
	readWriteFailure(error, GENERIC_COMMENT_WRITE_ERROR);

// ---------------------------------------------------------------------------
// Cache mutators. Pure, so the cache rules can be tested without React.
// ---------------------------------------------------------------------------

/** A new top-level comment. `pagination.total` counts conversations, so it moves. */
export const prependComment = (thread: CommentListResource, comment: CommentResource): CommentListResource => ({
	...thread,
	items: [comment, ...thread.items],
	pagination: { ...thread.pagination, total: thread.pagination.total + 1 },
});

/**
 * A new reply belongs to its root's subtree, not to the page, so it bumps
 * `replies_count` and leaves `pagination.total` alone.
 */
export const appendReply = (
	thread: CommentListResource,
	rootId: number,
	reply: CommentReplyResource,
): CommentListResource => ({
	...thread,
	items: thread.items.map((item) =>
		item.id === rootId
			? { ...item, replies: [...item.replies, reply], replies_count: item.replies_count + 1 }
			: item,
	),
});

/**
 * Replace an edited comment wherever it lives, matched by uuid because that is
 * the identity the update transport uses.
 *
 * A root keeps its loaded `replies` and `replies_count`: the edit response does
 * not re-read the subtree, so taking those from it would drop replies the
 * reader can already see.
 */
export const replaceComment = (
	thread: CommentListResource,
	updated: CommentResource | CommentReplyResource,
): CommentListResource => ({
	...thread,
	items: thread.items.map((item) => {
		if (item.uuid === updated.uuid) {
			return { ...item, ...updated, replies: item.replies, replies_count: item.replies_count };
		}

		return {
			...item,
			replies: item.replies.map((reply) => (reply.uuid === updated.uuid ? { ...reply, ...updated } : reply)),
		};
	}),
});

/**
 * Remove a comment, mirroring the backend cascade.
 *
 * Deleting a root takes its loaded replies with it and decrements
 * `pagination.total`; deleting a reply only decrements its root's
 * `replies_count`. The previous seam filtered one row out of a flat list and
 * adjusted no count at all.
 */
export const removeComment = (
	thread: CommentListResource,
	target: { id: number; parentCommentId: number | null },
): CommentListResource => {
	if (target.parentCommentId === null) {
		const remaining = thread.items.filter((item) => item.id !== target.id);

		return {
			...thread,
			items: remaining,
			pagination: {
				...thread.pagination,
				total: Math.max(0, thread.pagination.total - (thread.items.length - remaining.length)),
			},
		};
	}

	return {
		...thread,
		items: thread.items.map((item) => {
			const remaining = item.replies.filter((reply) => reply.id !== target.id);

			if (remaining.length === item.replies.length) {
				return item;
			}

			return {
				...item,
				replies: remaining,
				replies_count: Math.max(0, item.replies_count - (item.replies.length - remaining.length)),
			};
		}),
	};
};

/**
 * Optimistic edit: only the new text is known before the server answers, so
 * nothing else on the comment is touched.
 */
export const patchCommentContent = (
	thread: CommentListResource,
	uuid: string,
	content: string,
): CommentListResource => ({
	...thread,
	items: thread.items.map((item) => ({
		...(item.uuid === uuid ? { ...item, content } : item),
		replies: item.replies.map((reply) => (reply.uuid === uuid ? { ...reply, content } : reply)),
	})),
});

// ---------------------------------------------------------------------------
// Mutations
// ---------------------------------------------------------------------------

interface CommentThreadTarget {
	parent: CommentParent;
	entityId: number;
	entityUuid: string;
}

export type CreateCommentVariables = Pick<StoreCommentRequest, 'content' | 'parent_comment_id'>;

/**
 * One hook for a new comment and for a reply; the only difference is whether
 * `parent_comment_id` is sent.
 *
 * Deliberately not optimistic. The server assigns `id`, `uuid` and timestamps,
 * and the Like seam rejects anything that is not a loaded positive integer id,
 * so a placeholder row would render a like button that throws.
 */
export const useCreateCommentMutation = ({ parent, entityId, entityUuid }: CommentThreadTarget) => {
	const queryClient = useQueryClient();
	const queryKey = getCommentsQueryKey(parent, entityUuid);

	return useMutation<CommentResource, unknown, CreateCommentVariables>({
		mutationFn: (variables) =>
			commentStore({
				entity_type: COMMENT_PARENTS[parent].template,
				entity_id: entityId,
				entity_uuid: entityUuid,
				...variables,
			}),
		onSuccess: (created, variables) => {
			queryClient.setQueryData<CommentListResource>(queryKey, (thread) => {
				if (thread === undefined) return thread;

				return variables.parent_comment_id == null
					? prependComment(thread, created)
					: appendReply(thread, variables.parent_comment_id, created);
			});

			if (variables.parent_comment_id != null) {
				// An expanded reply list is a separate cache entry keyed by the
				// parent's uuid, which a reply's variables do not carry. Matching
				// on the generated key's shape reconciles whichever one is open.
				queryClient.invalidateQueries({
					predicate: (query) =>
						typeof query.queryKey[0] === 'string' && query.queryKey[0].endsWith('/replies'),
				});
			}
		},
	});
};

interface OptimisticThreadContext {
	previous: CommentListResource | undefined;
}

export interface UpdateCommentVariables {
	uuid: string;
	content: string;
}

export const useUpdateCommentMutation = ({ parent, entityUuid }: Omit<CommentThreadTarget, 'entityId'>) => {
	const queryClient = useQueryClient();
	const queryKey = getCommentsQueryKey(parent, entityUuid);

	return useMutation<CommentResource, unknown, UpdateCommentVariables, OptimisticThreadContext>({
		mutationFn: ({ uuid, content }) => commentUpdate(uuid, { content }),

		onMutate: async ({ uuid, content }) => {
			// An in-flight read would otherwise land after the optimistic write.
			await queryClient.cancelQueries({ queryKey });

			const previous = queryClient.getQueryData<CommentListResource>(queryKey);

			if (previous !== undefined) {
				queryClient.setQueryData<CommentListResource>(queryKey, patchCommentContent(previous, uuid, content));
			}

			return { previous };
		},

		// Restore the exact snapshot rather than reverting the edit, so a like or
		// delete that landed while the request was in flight is not reconstructed
		// from a stale guess.
		onError: (_error, _variables, context) => {
			if (context?.previous !== undefined) {
				queryClient.setQueryData<CommentListResource>(queryKey, context.previous);
			}
		},

		onSuccess: (updated) => {
			queryClient.setQueryData<CommentListResource>(queryKey, (thread) =>
				thread === undefined ? thread : replaceComment(thread, updated),
			);
		},
	});
};

export interface DeleteCommentVariables {
	id: number;
	uuid: string;
	parentCommentId: number | null;
}

/**
 * `uuid` addresses the transport, `id` and `parentCommentId` address the cache.
 * Neither substitutes for the other.
 */
export const useDeleteCommentMutation = ({ parent, entityUuid }: Omit<CommentThreadTarget, 'entityId'>) => {
	const queryClient = useQueryClient();
	const queryKey = getCommentsQueryKey(parent, entityUuid);

	// Echoes the target rather than resolving to nothing: the endpoint answers
	// 204, and an explicit `void` type argument trips
	// @typescript-eslint/no-invalid-void-type.
	return useMutation<DeleteCommentVariables, unknown, DeleteCommentVariables, OptimisticThreadContext>({
		mutationFn: async (target) => {
			await commentDestroy(target.uuid);

			return target;
		},

		onMutate: async (target) => {
			await queryClient.cancelQueries({ queryKey });

			const previous = queryClient.getQueryData<CommentListResource>(queryKey);

			if (previous !== undefined) {
				queryClient.setQueryData<CommentListResource>(queryKey, removeComment(previous, target));
			}

			return { previous };
		},

		onError: (_error, _variables, context) => {
			if (context?.previous !== undefined) {
				queryClient.setQueryData<CommentListResource>(queryKey, context.previous);
			}
		},
	});
};

/**
 * The rest of one comment's replies, beyond the preview the thread carries.
 *
 * Enabled on demand so an unexpanded thread costs nothing.
 */
export const useCommentRepliesQuery = (commentUuid: string, enabled: boolean) =>
	useQuery({
		queryKey: getCommentRepliesCacheKey(commentUuid),
		queryFn: () => commentReplies(commentUuid, { per_page: COMMENT_PAGE_SIZE }),
		enabled,
	});

// ---------------------------------------------------------------------------
// Likes
// ---------------------------------------------------------------------------

/**
 * A thread is cached as one list, so a like patches the single matching item in
 * place instead of refetching every comment for two numbers. The target may be
 * a top-level comment or one of its loaded replies.
 */
const buildCommentLikeBinding = (queryKey: QueryKey): LikeCacheBinding<CommentListResource> => ({
	queryKey,

	read: (thread, commentId) => {
		for (const item of thread.items) {
			if (item.id === commentId) {
				return { is_liked: item.viewer.is_liked, likes_count: item.likes_count };
			}

			const reply = item.replies.find((candidate) => candidate.id === commentId);

			if (reply) {
				return { is_liked: reply.viewer.is_liked, likes_count: reply.likes_count };
			}
		}

		return undefined;
	},

	write: (thread, commentId, next) => ({
		...thread,
		items: thread.items.map((item) => {
			if (item.id === commentId) {
				return { ...item, viewer: { ...item.viewer, is_liked: next.is_liked }, likes_count: next.likes_count };
			}

			return {
				...item,
				replies: item.replies.map((reply) =>
					reply.id === commentId
						? {
								...reply,
								viewer: { ...reply.viewer, is_liked: next.is_liked },
								likes_count: next.likes_count,
							}
						: reply,
				),
			};
		}),
	}),
});

/**
 * Toggles the like on one comment within a thread. The mutation variable is the
 * comment's loaded numeric `id`, which also selects which cached item is patched.
 */
export const useLikeCommentMutation = (queryKey: QueryKey) =>
	useToggleLikeMutation({
		template: ObjectTemplateType.COMMENT,
		binding: buildCommentLikeBinding(queryKey),
	});

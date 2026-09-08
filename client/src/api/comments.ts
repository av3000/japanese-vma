import type { QueryKey } from '@tanstack/react-query';
import { commentStore } from '@/api/generated/comment/comment';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import type { ObjectTemplateType as CommentEntityType } from '@/api/generated/model/objectTemplateType';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import { useToggleLikeMutation, type LikeCacheBinding } from '@/api/likes/likes';
import axios from '@/services/axios';
import { ObjectTemplateType } from '@/shared/constants/enums';

export type ApiComment = CommentResource;

export interface CommentFilters {
	include_likes?: boolean;
}

export interface RemoveCommentPayload {
	parentObjectType: string;
	parentObjectId: string | number;
	commentId: number;
}

export const fetchComments = async (
	objectType: string,
	objectId: string | number,
	filters?: CommentFilters,
): Promise<CommentListResource> => {
	// TODO: use generated Orval route for fetching comments
	const url = `v1/${objectType}s/${objectId}/comments`;

	const response = await axios.get<CommentListResource>(url, {
		params: filters,
	});

	return response.data;
};

export const addComment = async (
	entityType: CommentEntityType,
	entityId: number,
	entityUuid: string,
	requestPayload: Pick<StoreCommentRequest, 'content' | 'parent_comment_id'>,
): Promise<CommentResource> => {
	return commentStore({
		entity_type: entityType,
		entity_id: entityId,
		entity_uuid: entityUuid,
		...requestPayload,
	});
};

export const deleteComment = async (requestPayload: RemoveCommentPayload) => {
	return axios.delete(`${requestPayload.parentObjectType}/comment/${requestPayload.commentId}`, {
		params: {
			template_id: requestPayload.parentObjectId,
		},
	});
};

/**
 * Single source for the comment thread cache key.
 *
 * The thread query, the add/delete patches and the like toggle all reconcile the same list, so
 * they must not each build their own key.
 */
export const getCommentsQueryKey = (readObjectType: string, entityId: number): QueryKey => [
	'comments',
	readObjectType,
	entityId,
	{ include_likes: true },
];

/**
 * A comment thread is cached as one list, so a like patches the single matching item in place
 * instead of refetching every comment for two numbers.
 */
const buildCommentLikeBinding = (queryKey: QueryKey): LikeCacheBinding<CommentListResource> => ({
	queryKey,

	read: (thread, commentId) => {
		const comment = thread.items.find((item) => item.id === commentId);

		return comment ? { is_liked: comment.is_liked_by_viewer, likes_count: comment.likes_count } : undefined;
	},

	write: (thread, commentId, next) => ({
		...thread,
		items: thread.items.map((item) =>
			item.id === commentId
				? { ...item, is_liked_by_viewer: next.is_liked, likes_count: next.likes_count }
				: item,
		),
	}),
});

/**
 * Toggles the like on one comment within a thread. The mutation variable is the comment's loaded
 * numeric `id`, which also selects which cached item gets patched.
 */
export const useLikeCommentMutation = (queryKey: QueryKey) =>
	useToggleLikeMutation({
		template: ObjectTemplateType.COMMENT,
		binding: buildCommentLikeBinding(queryKey),
	});

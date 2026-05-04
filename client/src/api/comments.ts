import { commentStore } from '@/api/generated/comment/comment';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import type { ObjectTemplateType } from '@/api/generated/model/objectTemplateType';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import axios from '@/services/axios';

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
	entityType: ObjectTemplateType,
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

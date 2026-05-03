import { commentStore } from '@/api/generated/comment/comment';
import type { CommentStore200 } from '@/api/generated/model/commentStore200';
import type { CommentStore201 } from '@/api/generated/model/commentStore201';
import type { ObjectTemplateType } from '@/api/generated/model/objectTemplateType';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import axios from '@/services/axios';
import { PaginatedResponse } from '@/types';

export interface ApiComment {
	id: number;
	content: string;
	entity_uuid: string;
	author_name: string;
	author_id: number;
	created_at: string;
	updated_at: string;
	likes_count: number;
	is_liked_by_viewer: boolean;
}

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
): Promise<PaginatedResponse<ApiComment>> => {
	console.log('fetch comments call');
	const url = `v1/${objectType}s/${objectId}/comments`;

	const response = await axios.get(url, {
		params: filters,
	});
	console.log('response comments: ', response);
	return response.data.data || [];
};

export const addComment = async (
	entityType: ObjectTemplateType,
	entityId: number,
	entityUuid: string,
	requestPayload: Pick<StoreCommentRequest, 'content' | 'parent_comment_id'>,
): Promise<CommentStore200['data'] | CommentStore201['data']> => {
	const response = await commentStore({
		entity_type: entityType,
		entity_id: entityId,
		entity_uuid: entityUuid,
		...requestPayload,
	});

	return response.data;
};

export const deleteComment = async (requestPayload: RemoveCommentPayload) => {
	return axios.delete(`${requestPayload.parentObjectType}/comment/${requestPayload.commentId}`, {
		params: {
			template_id: requestPayload.parentObjectId,
		},
	});
};

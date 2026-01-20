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

export interface AddCommentPayload {
	content: string;
	entity_id?: string;
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

	return response.data || [];
};

export const addComment = async (
	parentObjectType: string,
	parentObjectId: string | number,
	requestPayload: AddCommentPayload,
) => {
	const response = await axios.post(`${parentObjectType}/${parentObjectId}/comment`, requestPayload);
	return response.data.comment;
};

export const deleteComment = async (requestPayload: RemoveCommentPayload) => {
	return axios.delete(`${requestPayload.parentObjectType}/comment/${requestPayload.commentId}`, {
		params: {
			template_id: requestPayload.parentObjectId,
		},
	});
};

import axios from '@/services/axios';
import { PaginatedResponse } from '@/types';

export interface LikeUser {
	id: number;
	uuid: string;
	name: string;
}

export interface Like {
	id: string;
	value: number;
	created_at: string;
	user: LikeUser;
}

export interface ApiComment {
	id: number;
	content: string;
	entity_uuid: string;
	author_name: string;
	author_id: number;
	created_at: string;
	updated_at: string;
	likes: Like[];
	isLiked: boolean;
}

export interface Comment extends ApiComment {
	isLiked: boolean;
	likesTotal: number;
}

export interface CommentFilters {
	include_likes?: boolean;
}

export interface AddCommentPayload {
	content: string;
	entity_id?: string;
}

export interface RemoveCommentPayload {
	objectType: string;
	objectTypeId: number;
	commentId: number;
}

export interface LikeRequestPayload {
	objectType: string;
	objectTypeId: number;
	commentId: number;
}

export const fetchComments = async (
	objectType: string,
	objectId: string | number,
	filters?: CommentFilters,
): Promise<PaginatedResponse<ApiComment>> => {
	const url = `v1/${objectType}s/${objectId}/comments`;

	const response = await axios.get(url, {
		params: filters,
	});

	return response.data || [];
};

export const addComment = async (objectType: string, objectId: string | number, requestPayload: AddCommentPayload) => {
	const response = await axios.post(`${objectType}/${objectId}/comment`, requestPayload);
	return response.data.comment;
};

export const deleteComment = async (requestPayload: RemoveCommentPayload) => {
	return axios.delete(`${requestPayload.objectType}/comment/${requestPayload.commentId}`, {
		params: {
			template_id: requestPayload.objectTypeId,
		},
	});
};

export const toggleCommentLike = async (requestPayload: LikeRequestPayload): Promise<Like | false> => {
	const response = await axios.post(`${requestPayload.objectType}/comment/like`, {
		template_id: requestPayload.objectTypeId,
		real_object_id: requestPayload.commentId,
	});

	return response.data.like;
};

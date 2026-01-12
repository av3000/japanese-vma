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

interface CommentFilters {
	include_likes?: boolean;
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

export const addComment = async (objectType: string, objectId: string | number, content: string) => {
	const response = await axios.post(`${objectType}/${objectId}/comment`, { content });
	return response.data.comment;
};

export const deleteComment = async (objectType: string, objectId: string | number, commentId: number) => {
	return axios.delete(`${objectType}/${objectId}/comment/${commentId}`);
};

export const toggleCommentLike = async (
	objectType: string,
	objectId: string | number,
	commentId: number,
	isLiked: boolean,
) => {
	const endpoint = isLiked ? 'unlike' : 'like';
	return axios.post(`${objectType}/${objectId}/comment/${commentId}/${endpoint}`);
};

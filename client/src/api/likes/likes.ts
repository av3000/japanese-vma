import axios from '@/services/axios';

export interface LikeRequestPayload {
	objectType: string;
	objectTypeId: number;
	instanceId: number;
}

export interface LikeResponse {
	success: boolean;
	like: boolean;
}

export const toggleCommentLike = async (requestPayload: LikeRequestPayload): Promise<LikeResponse> => {
	const response = await axios.post(`/v1/like-instance`, {
		template_id: requestPayload.objectTypeId,
		real_object_id: requestPayload.instanceId,
	});

	return response.data.like;
};

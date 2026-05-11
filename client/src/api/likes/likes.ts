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

// Shared legacy-named wrapper over the generic v1 `like-instance` endpoint. Callers currently
// include comments, articles, and catalogues, so the helper name should reflect the endpoint
// contract rather than a single content type.
export const toggleInstanceLike = async (requestPayload: LikeRequestPayload): Promise<LikeResponse> => {
	const response = await axios.post(`/v1/like-instance`, {
		template_id: requestPayload.objectTypeId,
		real_object_id: requestPayload.instanceId,
	});

	return response.data.like;
};

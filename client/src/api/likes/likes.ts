import axios from '@/services/axios';

export interface LikeRequestPayload {
	objectType: string;
	objectTypeId: number;
	instanceId: number;
}

export interface LikeResponse {
	is_liked: boolean;
	likes_count: number;
}

// Shared legacy-named wrapper over the generic v1 `like-instance` endpoint. Callers currently
// include comments, articles, and catalogues, so the helper name should reflect the endpoint
// contract rather than a single content type.
// TODO(LIKE-FE-01): replace with the Orval-generated hook now that the endpoint emits a typed model.
export const toggleInstanceLike = async (requestPayload: LikeRequestPayload): Promise<LikeResponse> => {
	const response = await axios.post<LikeResponse>(`/v1/like-instance`, {
		template_id: requestPayload.objectTypeId,
		real_object_id: requestPayload.instanceId,
	});

	return response.data;
};

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
	// TODO: Backend endpoint must be moved to different separe controller to be used for all liking features,
	// right now it comes from Http/Controllers/ArticleController and is couple to `article/comment/like` route
	// thus objectType must be `article` right now, after migration, it will be simply `/like` with request params
	console.log('togglelike payload:', requestPayload);
	const response = await axios.post(`/v1/like-instance`, {
		template_id: requestPayload.objectTypeId,
		real_object_id: requestPayload.instanceId,
	});

	console.log('togglelike respo', response.data);
	return response.data.like;
};

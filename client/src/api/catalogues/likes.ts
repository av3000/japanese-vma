import { toggleInstanceLike } from '@/api/likes/likes';
import { apiCall } from '@/services/api';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { HttpMethod } from '@/shared/types';

// TODO: replace with a backend/Orval-generated response model if the catalogue like-status
// check is exposed through a documented v1 endpoint instead of the legacy route.
interface LegacyCatalogueLikeResponse {
	isLiked: boolean;
}

export const fetchCatalogueLikeStatus = async (catalogueId: number) => {
	const response = await apiCall<LegacyCatalogueLikeResponse>({
		method: HttpMethod.POST,
		path: `/list/${catalogueId}/checklike`,
	});

	return response.isLiked;
};

export const toggleCatalogueLike = async (catalogueId: number) => {
	return toggleInstanceLike({
		objectType: ObjectTemplateTypeLabel[ObjectTemplateType.LIST],
		objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.LIST],
		instanceId: catalogueId,
	});
};

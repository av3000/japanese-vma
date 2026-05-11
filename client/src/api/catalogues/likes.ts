import { toggleInstanceLike } from '@/api/likes/likes';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';

export const toggleCatalogueLike = async (catalogueId: number) => {
	return toggleInstanceLike({
		objectType: ObjectTemplateTypeLabel[ObjectTemplateType.LIST],
		objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.LIST],
		instanceId: catalogueId,
	});
};

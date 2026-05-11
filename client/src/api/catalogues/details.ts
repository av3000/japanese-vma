import { useMutation, useQueryClient } from '@tanstack/react-query';
import { getCatalogueShowQueryKey, useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { LikeResponse, toggleInstanceLike } from '@/api/likes/likes';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';

export interface MappedCatalogue extends CatalogueDetailResource {
	displayName: string;
	formattedDate: string;
}

export const mapCatalogueDetail = (data: CatalogueDetailResource): MappedCatalogue => ({
	...data,
	displayName: data.owner?.name || 'Unknown Author',
	formattedDate: new Date(data.created_at).toLocaleDateString(),
});

export const useCatalogueQuery = (uuid: string | undefined) => {
	return useCatalogueShow<MappedCatalogue>(uuid ?? '', {
		query: {
			enabled: !!uuid,
			retry: false,
			select: mapCatalogueDetail,
		},
	});
};

export const useLikeCatalogueMutation = (catalogueUuid: string) => {
	const queryClient = useQueryClient();

	return useMutation<LikeResponse, unknown, number>({
		mutationFn: (catalogueId: number) =>
			toggleInstanceLike({
				objectType: ObjectTemplateTypeLabel[ObjectTemplateType.LIST],
				objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.LIST],
				instanceId: catalogueId,
			}),
		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: getCatalogueShowQueryKey(catalogueUuid) });
		},
		onError: (error) => {
			console.error('Like catalogue failed', error);
		},
	});
};

import { getCatalogueShowQueryKey, useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { useToggleLikeMutation, type LikeCacheBinding } from '@/api/likes/likes';
import { ObjectTemplateType } from '@/shared/constants/enums';

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

/**
 * Catalogue detail carries the viewer's own like state, so the toggle patches the cached record
 * rather than invalidating a payload that also holds every catalogue item.
 */
const buildCatalogueLikeBinding = (catalogueUuid: string): LikeCacheBinding<CatalogueDetailResource> => ({
	queryKey: getCatalogueShowQueryKey(catalogueUuid),

	read: (catalogue) => ({
		is_liked: catalogue.engagement.is_liked_by_viewer,
		likes_count: catalogue.engagement.likes_count,
	}),

	write: (catalogue, _instanceId, next) => ({
		...catalogue,
		engagement: {
			...catalogue.engagement,
			is_liked_by_viewer: next.is_liked,
			likes_count: next.likes_count,
		},
	}),
});

/**
 * Toggles the like on the catalogue the detail route is showing. The mutation variable is the
 * catalogue's loaded numeric `id`; the uuid only addresses the cache.
 */
export const useLikeCatalogueMutation = (catalogueUuid: string) =>
	useToggleLikeMutation({
		template: ObjectTemplateType.LIST,
		binding: buildCatalogueLikeBinding(catalogueUuid),
	});

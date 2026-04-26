import { isCatalogueRouteUuid } from '@/shared/constants/catalogues';

interface CatalogueRouteState {
	hasUuidParam: boolean;
	resolvedUuid: string | undefined;
	shouldResolveLegacyIdentity: boolean;
}

export const getCatalogueRouteState = (
	catalogueId: string | undefined,
	legacyUuid?: string,
): CatalogueRouteState => {
	const hasUuidParam = Boolean(catalogueId && isCatalogueRouteUuid(catalogueId));

	return {
		hasUuidParam,
		resolvedUuid: hasUuidParam ? catalogueId : legacyUuid,
		shouldResolveLegacyIdentity: Boolean(catalogueId && !hasUuidParam),
	};
};

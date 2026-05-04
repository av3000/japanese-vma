import { customInstance } from '@/services/orval-mutator';
import { downloadLegacyCataloguePdf } from './legacyCatalogues';

// TODO: switch to the generated v1 catalogue delete client once the worktree/OpenAPI/Orval state
// is aligned with the already-available UUID route.
export const deleteCatalogue = async (catalogueUuid: string) => {
	await customInstance<unknown>({
		url: `/catalogues/${encodeURIComponent(catalogueUuid)}`,
		method: 'DELETE',
	});
};

// PDF export remains on the legacy adapter because this worktree path does not yet have a v1
// replacement for the export route.
export const downloadCataloguePdf = async (catalogueId: number, catalogueType: number) => {
	return downloadLegacyCataloguePdf(catalogueId, catalogueType);
};

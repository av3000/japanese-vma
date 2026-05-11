import { downloadLegacyCataloguePdf } from './legacyCatalogues';

// PDF export remains on the legacy adapter because this worktree path does not yet have a v1
// replacement for the export route.
export const downloadCataloguePdf = async (catalogueId: number, catalogueType: number) => {
	return downloadLegacyCataloguePdf(catalogueId, catalogueType);
};

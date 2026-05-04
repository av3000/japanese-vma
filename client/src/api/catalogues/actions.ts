import axios from '@/services/axios';
import { customInstance } from '@/services/orval-mutator';
import { downloadLegacyCataloguePdf } from './legacyCatalogues';
import type { CatalogueMembershipAction } from './bookmarkMembership';

// TODO: switch to the generated v1 catalogue delete client once the worktree/OpenAPI/Orval state
// is aligned with the already-available UUID route.
export const deleteCatalogue = async (catalogueUuid: string) => {
	await customInstance<unknown>({
		url: `/catalogues/${encodeURIComponent(catalogueUuid)}`,
		method: 'DELETE',
	});
};

export const updateCatalogueMembership = async ({
	catalogueId,
	elementId,
	action,
}: {
	catalogueId: number;
	elementId: string | number;
	action: CatalogueMembershipAction;
}) => {
	// TODO: replace the legacy membership compatibility endpoints
	// `user/list/additemwhileaway` and `user/list/removeitemwhileaway` with the v1 membership
	// routes once frontend generation and this worktree's OpenAPI/Orval state are aligned.
	const endpoint = action === 'add' ? 'additemwhileaway' : 'removeitemwhileaway';

	return axios.post(`user/list/${endpoint}`, {
		listId: catalogueId,
		elementId,
	});
};

// PDF export remains on the legacy adapter because this worktree path does not yet have a v1
// replacement for the export route.
export const downloadCataloguePdf = async (catalogueId: number, catalogueType: number) => {
	return downloadLegacyCataloguePdf(catalogueId, catalogueType);
};

import axios from '@/services/axios';
import { catalogueAddItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import { resolveLegacyCatalogueIdentity } from './legacyCatalogues';

// Transitional adapter: this file still fetches bookmark membership from a legacy endpoint,
// normalizes each entry into a canonical UUID-bearing catalogue reference, and exposes small
// helpers over that normalized shape until bookmark membership is served directly by v1.

// TODO: replace with a backend/Orval-generated response model once the bookmark-membership
// endpoint is exposed in v1 and documented in the OpenAPI schema.
interface BookmarkMembershipResponse {
	lists?: RawBookmarkMembershipListItem[];
}

// TODO: replace with a backend/Orval-generated list item model once the bookmark-membership
// endpoint has a typed schema instead of this legacy response shape.
interface RawBookmarkMembershipListItem {
	id: number;
	uuid?: string | null;
	title: string;
	type: number;
	elementBelongsToList?: boolean;
}

// TODO: replace this normalized DTO with a generated backend/Orval type once the
// membership endpoint returns canonical UUID-bearing catalogue data directly.
export interface CatalogueBookmarkListItem {
	id: number;
	uuid: string;
	title: string;
	type: number;
	elementBelongsToList: boolean;
}

// Fetches membership from the legacy bookmark endpoint before the response is normalized into
// canonical catalogue references that downstream UI code can treat like v1 catalogue data.
export const fetchElementCatalogueMembership = async (
	elementId: string | number,
): Promise<CatalogueBookmarkListItem[]> => {
	// TODO: replace `user/lists/contain` with the v1 bookmark-membership route once frontend
	// generation and this worktree's OpenAPI/Orval state are aligned.
	const response = await axios.post<BookmarkMembershipResponse>('user/lists/contain', { elementId });
	const lists = response.data.lists ?? [];

	return Promise.all(lists.map(normalizeBookmarkMembershipItem));
};

// Normalizes a legacy membership item into the canonical UUID-bearing catalogue shape, resolving
// legacy numeric-only identities on demand when the endpoint omits the UUID.
const normalizeBookmarkMembershipItem = async (
	list: RawBookmarkMembershipListItem,
): Promise<CatalogueBookmarkListItem> => {
	if (list.uuid) {
		return {
			id: list.id,
			uuid: list.uuid,
			title: list.title,
			type: list.type,
			elementBelongsToList: Boolean(list.elementBelongsToList),
		};
	}

	const legacyIdentity = await resolveLegacyCatalogueIdentity(list.id);

	return {
		id: list.id,
		uuid: legacyIdentity.uuid,
		title: legacyIdentity.title,
		type: list.type,
		elementBelongsToList: Boolean(list.elementBelongsToList),
	};
};

// Filters the normalized catalogue membership set so consumers can keep legacy numeric type logic
// out of route components and JSX.
export const filterCatalogueMembershipByType = (
	lists: CatalogueBookmarkListItem[],
	allowedTypes: number[],
): CatalogueBookmarkListItem[] => {
	const allowedTypeSet = new Set(allowedTypes);
	return lists.filter((list) => allowedTypeSet.has(list.type));
};

export type CatalogueMembershipAction = 'add' | 'remove';

export const updateElementCatalogueMembership = async ({
	list,
	elementId,
	action,
}: {
	list: CatalogueBookmarkListItem;
	elementId: number;
	action: CatalogueMembershipAction;
}) => {
	if (action === 'add') {
		await catalogueAddItem(list.uuid, { item_id: elementId });
		return;
	}

	await catalogueRemoveItem(list.uuid, elementId);
};

// Applies the optimistic local membership change on the normalized result without re-exposing the
// legacy endpoint response shape to callers.
export const applyCatalogueMembershipAction = (
	lists: CatalogueBookmarkListItem[],
	catalogueId: number,
	action: CatalogueMembershipAction,
): CatalogueBookmarkListItem[] => {
	return lists.map((list) =>
		list.id === catalogueId
			? {
					...list,
					elementBelongsToList: action === 'add',
				}
			: list,
	);
};

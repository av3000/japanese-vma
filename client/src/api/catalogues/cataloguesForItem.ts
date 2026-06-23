import { catalogueAddItem, catalogueForItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import type { CatalogueForItem200ItemsItem } from '@/api/generated/model/catalogueForItem200ItemsItem';

export type CatalogueForItem = CatalogueForItem200ItemsItem;

interface FetchCataloguesForItemOptions {
	types?: number[];
	search?: string;
}

export const fetchCataloguesForItem = async (
	elementId: string | number,
	options: FetchCataloguesForItemOptions = {},
): Promise<CatalogueForItem[]> => {
	const itemId = Number(elementId);

	if (Number.isNaN(itemId)) {
		return [];
	}

	const response = await catalogueForItem({
		item_id: itemId,
		...(options.types ? { 'types[]': options.types } : {}),
		...(options.search ? { search: options.search } : {}),
	});

	return response.items;
};

export type CatalogueForItemAction = 'add' | 'remove';

export const deriveCatalogueWidgetState = (lists: CatalogueForItem[], isKnownType?: number) => ({
	isBookmarked: lists.some((list) => list.contains_item),
	isKnown: isKnownType ? lists.some((list) => list.contains_item && list.type === isKnownType) : false,
});

export const addOrRemoveCatalogueForItem = async ({
	list,
	elementId,
	action,
}: {
	list: CatalogueForItem;
	elementId: number;
	action: CatalogueForItemAction;
}) => {
	if (action === 'add') {
		await catalogueAddItem(list.uuid, { item_id: elementId });
		return;
	}

	await catalogueRemoveItem(list.uuid, elementId);
};

// Applies the optimistic local for-item change on the shared result shape.
export const optimisticApplyCatalogueForItemAction = (
	lists: CatalogueForItem[],
	catalogueId: number,
	action: CatalogueForItemAction,
): CatalogueForItem[] => {
	return lists.map((list) =>
		list.id === catalogueId
			? {
					...list,
					contains_item: action === 'add',
				}
			: list,
	);
};

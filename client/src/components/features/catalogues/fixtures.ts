import type { CatalogueForItem } from '@/api/catalogues/cataloguesForItem';
import { SavedListType } from '@/shared/constants/enums';

const uuidFor = (id: number) => `00000000-0000-4000-8000-${String(id).padStart(12, '0')}`;

/** One of the viewer's catalogues as the for-item endpoint reports it for a single item. */
export const catalogueForItem = (id: number, fields: Partial<CatalogueForItem> = {}): CatalogueForItem => ({
	id,
	uuid: uuidFor(id),
	title: `Catalogue ${id}`,
	type: SavedListType.ARTICLES,
	type_label: 'Articles',
	publicity: 1,
	contains_item: false,
	...fields,
});

/** Three article catalogues, the middle one already holding the item. */
export const someSavedLists: CatalogueForItem[] = [
	catalogueForItem(1, { title: 'Reading list for N3' }),
	catalogueForItem(2, { title: 'Station announcements', contains_item: true }),
	catalogueForItem(3, { title: '新幹線の旅', publicity: 0 }),
];

/** More catalogues than fit on screen, so the modal has to scroll. */
export const manyLists: CatalogueForItem[] = Array.from({ length: 30 }, (_, index) =>
	catalogueForItem(index + 1, {
		title: index === 4 ? `${'A very long catalogue title '.repeat(4)}that keeps going` : `Catalogue ${index + 1}`,
		contains_item: index % 7 === 0,
	}),
);

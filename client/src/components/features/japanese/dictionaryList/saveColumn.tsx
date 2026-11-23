import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import type { DataTableColumn, DataTableStackedLayout } from '@/components/shared/DataTable';
import type { SavedListType } from '@/shared/constants/enums';

/** The column id, which is also the `save` grid area of a stacked layout. */
const SAVE_COLUMN_ID = 'save';

interface SaveColumnOptions<Row> {
	/** The saved list type and the "known" list type this item is filed under. */
	types: { saved: SavedListType; known: SavedListType };
	/** Title of the "choose a list" dialog, e.g. "Choose Kanji List to add". */
	modalTitle: string;
	getId: (row: Row) => number;
	/** Names the button for assistive technology: "Save 水", "Saved: 水". */
	getLabel: (row: Row) => string | null | undefined;
	/** The viewer's state the list payload already carries; no request is made per row. */
	getState: (row: Row) => ViewerCatalogueStateResource | null | undefined;
	/** Called once per change, so the route can write it into the list cache. */
	onChange?: (id: number, state: ViewerCatalogueStateResource) => void;
	cellClassName?: string;
}

/**
 * The Save column for signed-in viewers. Build it with `useMemo` keyed on `onChange`: a new column
 * array makes every row re-render, and a stable one lets `DataTable` skip the untouched rows.
 */
export const saveColumn = <Row,>({
	types,
	modalTitle,
	getId,
	getLabel,
	getState,
	onChange,
	cellClassName,
}: SaveColumnOptions<Row>): DataTableColumn<Row> => ({
	id: SAVE_COLUMN_ID,
	header: 'Save',
	headerHidden: true,
	width: 'shrink',
	cellClassName,
	cell: (row) => {
		const id = getId(row);
		const state = getState(row);

		return (
			<AuthorizedBookmarkWidget
				compact
				itemLabel={getLabel(row) ?? undefined}
				instanceObjectType={types.saved}
				isKnownType={types.known}
				entityId={id}
				modalTitle={modalTitle}
				initialIsBookmarked={state?.is_saved ?? false}
				initialIsKnown={state?.is_known ?? false}
				loadOnMount={false}
				onStateChange={({ isBookmarked, isKnown }) =>
					onChange?.(id, { is_saved: isBookmarked, is_known: isKnown })
				}
			/>
		);
	},
});

/** A stacked layout with the Save column added as an `auto` track on the right of every line. */
export const withSaveArea = ({ columns, areas }: DataTableStackedLayout): DataTableStackedLayout => ({
	columns: `${columns} auto`,
	areas: areas.map((area) => `${area} ${SAVE_COLUMN_ID}`),
});

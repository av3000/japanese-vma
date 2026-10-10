import type { DataTableColumn, DataTableStackedLayout } from '@/components/shared/DataTable';

/**
 * Appends caller-owned columns after a table's own, e.g. the owner's Remove column on Catalogue
 * detail (#347). In stacked rows each one gets an `auto` track on the right of every line, the way
 * the Save column does, and the layout is unchanged when there are none.
 */
export const withTrailingColumns = <Row>(
	columns: ReadonlyArray<DataTableColumn<Row>>,
	trailing: ReadonlyArray<DataTableColumn<Row>> | undefined,
): ReadonlyArray<DataTableColumn<Row>> => (trailing?.length ? [...columns, ...trailing] : columns);

export const withTrailingAreas = <Row>(
	layout: DataTableStackedLayout | undefined,
	trailing: ReadonlyArray<DataTableColumn<Row>> | undefined,
): DataTableStackedLayout | undefined => {
	if (!layout || !trailing?.length) return layout;

	const ids = trailing.map((column) => column.id).join(' ');

	return {
		columns: `${layout.columns}${' auto'.repeat(trailing.length)}`,
		areas: layout.areas.map((area) => `${area} ${ids}`),
	};
};

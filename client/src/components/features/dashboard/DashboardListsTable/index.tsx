import * as React from 'react';
import { Link } from 'react-router-dom';
import type { CatalogueResource } from '@/api/generated/model';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import { CATALOGUE_ROUTES, isBuiltInCatalogueType } from '@/shared/constants/catalogues';
import { actionsCellClassName, DateCell, RowActions, VisibilityCell, VisuallyHidden } from '../dashboardCells';
import { formatCount, toCount } from '../dashboardValues';
import styles from './DashboardListsTable.module.css';

export interface DashboardListsTableProps {
	catalogues: readonly CatalogueResource[];
	loading?: boolean;
	empty: DataTableEmpty;
	/** Asks the page to confirm and delete; the table owns no dialog. */
	onDelete: (catalogue: CatalogueResource) => void;
}

const stat = (catalogue: CatalogueResource, key: 'views_count' | 'downloads_count') =>
	formatCount(toCount(catalogue.engagement?.[key]));

const baseColumns: DataTableColumn<CatalogueResource>[] = [
	{
		id: 'title',
		header: 'Title',
		rowHeader: true,
		width: 'fill',
		cellClassName: styles.titleCell,
		cell: (catalogue) => (
			<Link to={CATALOGUE_ROUTES.detail(catalogue.uuid)} className={styles.title}>
				{catalogue.title}
			</Link>
		),
	},
	{
		id: 'type',
		header: 'Type',
		cell: (catalogue) => (
			<span className={styles.type}>
				{catalogue.type_label}
				{isBuiltInCatalogueType(catalogue.type) ? <span className={styles.builtIn}>Built-in</span> : null}
			</span>
		),
	},
	{
		id: 'items',
		header: 'Items',
		numeric: true,
		mobileLabel: 'Items',
		cell: (catalogue) => formatCount(toCount(catalogue.items_count)),
	},
	{
		id: 'visibility',
		header: 'Visibility',
		cell: (catalogue) => <VisibilityCell publicity={catalogue.publicity} />,
	},
	{
		id: 'views',
		header: 'Views',
		numeric: true,
		mobileLabel: 'Views',
		cell: (catalogue) => stat(catalogue, 'views_count'),
	},
	{
		id: 'downloads',
		header: 'Downloads',
		numeric: true,
		priority: 'low',
		mobileLabel: 'Downloads',
		cell: (catalogue) => stat(catalogue, 'downloads_count'),
	},
	{
		id: 'updated',
		header: 'Updated',
		mobileLabel: 'Updated',
		cell: (catalogue) => <DateCell iso={catalogue.updated_at} />,
	},
];

/** Title, then type and visibility, then the numbers, then the date and the actions. */
const STACKED = {
	columns: 'auto auto minmax(0, 1fr)',
	areas: ['title title title', 'type visibility visibility', 'items views downloads', 'updated updated actions'],
};

/**
 * The signed-in user's lists as an Index table (UI-DASH-04, #454). The built-in Known lists are
 * shown, because the dashboard is the only place they are reachable, but get no actions:
 * Known marking depends on them and nothing recreates a deleted one.
 */
export const DashboardListsTable: React.FC<DashboardListsTableProps> = ({ catalogues, loading, empty, onDelete }) => {
	const columns = React.useMemo<DataTableColumn<CatalogueResource>[]>(
		() => [
			...baseColumns,
			{
				id: 'actions',
				header: 'Actions',
				headerHidden: true,
				cellClassName: actionsCellClassName,
				cell: (catalogue) =>
					isBuiltInCatalogueType(catalogue.type) ? (
						<VisuallyHidden>Built-in lists cannot be edited or deleted</VisuallyHidden>
					) : (
						<RowActions
							name={catalogue.title}
							editTo={CATALOGUE_ROUTES.edit(catalogue.uuid)}
							onDelete={() => onDelete(catalogue)}
						/>
					),
			},
		],
		[onDelete],
	);

	return (
		<DataTable
			label="Your lists"
			columns={columns}
			rows={catalogues}
			getRowKey={(catalogue) => catalogue.uuid}
			loading={loading}
			empty={empty}
			stacked={STACKED}
		/>
	);
};

export default DashboardListsTable;

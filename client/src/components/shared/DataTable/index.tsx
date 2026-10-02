import * as React from 'react';
import classNames from 'classnames';
import styles from './DataTable.module.css';

export interface DataTableColumn<Row> {
	/** Stable key. Also the cell's `grid-area` name in a `stacked` layout, so keep it a plain identifier. */
	id: string;
	header: React.ReactNode;
	/** Free-form content: text, links, badges, buttons. */
	cell: (row: Row) => React.ReactNode;
	/** Exactly one column per table: rendered as `<th scope="row">`, it names the row. */
	rowHeader?: boolean;
	/** Right-aligned, mono, tabular figures. */
	numeric?: boolean;
	/** `low` columns are hidden from 768 to 1023px, where the table is narrowest. */
	priority?: 'low';
	/** Prefix shown before the value in stacked rows, where the column header is not visible. */
	mobileLabel?: string;
	/** Keeps the header for assistive technology but hides it visually, e.g. an action column. */
	headerHidden?: boolean;
	/** `fill` absorbs spare width (and holds a minimum); `shrink` takes only what its content needs. */
	width?: 'fill' | 'shrink';
	/** Extra class for this column's cells, for content styling owned by the caller. */
	cellClassName?: string;
}

/** Grid for stacked rows below 768px. Area names are column ids; a missing column leaves its area empty. */
export interface DataTableStackedLayout {
	/** A `grid-template-columns` value, e.g. `'48px minmax(0, 1fr) auto'`. */
	columns: string;
	/** One string per grid row, e.g. `['glyph meaning save', 'glyph on save']`. */
	areas: readonly string[];
}

export interface DataTableEmpty {
	title: React.ReactNode;
	hint?: React.ReactNode;
}

export interface DataTableProps<Row> {
	/** Accessible name of the table. */
	label: string;
	columns: ReadonlyArray<DataTableColumn<Row>>;
	rows: readonly Row[];
	getRowKey: (row: Row) => string;
	/** Renders skeleton rows under the real header instead of `rows`. */
	loading?: boolean;
	loadingRowCount?: number;
	/** Shown in a dashed panel instead of the table when there are no rows and nothing is loading. */
	empty?: DataTableEmpty;
	/** Without a layout, stacked rows show one cell per line. */
	stacked?: DataTableStackedLayout;
	className?: string;
}

type StackedStyle = React.CSSProperties & Record<'--data-table-columns' | '--data-table-areas', string>;

const stackedStyle = (layout: DataTableStackedLayout | undefined): StackedStyle | undefined =>
	layout && {
		'--data-table-columns': layout.columns,
		'--data-table-areas': layout.areas.map((area) => `"${area}"`).join(' '),
	};

const widthClass = { fill: styles.fill, shrink: styles.shrink } as const;

const columnClasses = <Row,>(column: DataTableColumn<Row>) =>
	classNames(
		column.numeric && styles.numeric,
		column.priority === 'low' && styles.lowPriority,
		column.width && widthClass[column.width],
	);

/**
 * One header cell. Sorting (#426) will hang off this: a sortable column renders its header as a
 * button with `aria-sort`, and nothing else in the table changes.
 */
const HeaderCell = <Row,>({ column }: { column: DataTableColumn<Row> }) => (
	<th role="columnheader" scope="col" className={columnClasses(column)}>
		{column.headerHidden ? <span className={styles.visuallyHidden}>{column.header}</span> : column.header}
	</th>
);

const BodyCell = <Row,>({ column, children }: { column: DataTableColumn<Row>; children: React.ReactNode }) => {
	const className = classNames(columnClasses(column), column.cellClassName);
	const style = { gridArea: column.id };
	const content = (
		<>
			{column.mobileLabel ? (
				<span className={styles.mobileLabel} aria-hidden="true">
					{column.mobileLabel}
				</span>
			) : null}
			{children}
		</>
	);

	return column.rowHeader ? (
		<th role="rowheader" scope="row" className={classNames(styles.rowHeader, className)} style={style}>
			{content}
		</th>
	) : (
		// eslint-disable-next-line jsx-a11y/no-interactive-element-to-noninteractive-role -- explicit roles survive the stacked layout, see DataTable
		<td role="cell" className={className} style={style}>
			{content}
		</td>
	);
};

/**
 * A real `<table>` with a sticky header, for index pages. From 768px it is a table; below that the
 * same markup becomes stacked row cards. The explicit ARIA roles keep table semantics when CSS
 * changes `display`, which Safari/VoiceOver otherwise drop.
 *
 * It owns no data, state or sorting: rows come in already ordered, and each cell renders whatever
 * its column returns.
 */
export const DataTable = <Row,>({
	label,
	columns,
	rows,
	getRowKey,
	loading = false,
	loadingRowCount = 6,
	empty,
	stacked,
	className,
}: DataTableProps<Row>) => {
	if (!loading && rows.length === 0 && empty) {
		return (
			<div className={classNames(styles.empty, className)} role="status">
				<h2 className={styles.emptyTitle}>{empty.title}</h2>
				{empty.hint ? <p className={styles.emptyHint}>{empty.hint}</p> : null}
			</div>
		);
	}

	return (
		<table
			role="table"
			aria-label={label}
			aria-busy={loading || undefined}
			className={classNames(styles.table, stacked && styles.gridStack, className)}
			style={stackedStyle(stacked)}
		>
			{/* eslint-disable-next-line jsx-a11y/no-redundant-roles -- explicit roles survive the stacked layout, see DataTable */}
			<thead role="rowgroup" className={styles.head}>
				<tr role="row">
					{columns.map((column) => (
						<HeaderCell key={column.id} column={column} />
					))}
				</tr>
			</thead>
			{/* eslint-disable-next-line jsx-a11y/no-redundant-roles -- see thead */}
			<tbody role="rowgroup">
				{loading
					? Array.from({ length: loadingRowCount }, (_, index) => (
							<tr role="row" key={`loading-${index}`} aria-hidden="true" className={styles.loadingRow}>
								{columns.map((column) => (
									<BodyCell key={column.id} column={{ ...column, mobileLabel: undefined }}>
										<span className={column.rowHeader ? styles.skeletonHead : styles.skeleton} />
									</BodyCell>
								))}
							</tr>
						))
					: rows.map((row) => (
							<tr role="row" key={getRowKey(row)}>
								{columns.map((column) => (
									<BodyCell key={column.id} column={column}>
										{column.cell(row)}
									</BodyCell>
								))}
							</tr>
						))}
			</tbody>
		</table>
	);
};

export default DataTable;

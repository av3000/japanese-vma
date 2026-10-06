import * as React from 'react';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import styles from './dashboardCells.module.css';
import { formatDashboardDate, visibilityDisplay } from './dashboardValues';

/** Cell content shared by the dashboard tables. */

export const VisuallyHidden: React.FC<{ children: React.ReactNode }> = ({ children }) => (
	<span className={styles.visuallyHidden}>{children}</span>
);

/**
 * A prefix shown only in stacked rows, for a cell that is sometimes empty (where the table's own
 * `mobileLabel` would print a label with nothing after it). Hidden from assistive technology,
 * which reads the column header instead.
 */
export const StackedLabel: React.FC<{ children: React.ReactNode }> = ({ children }) => (
	<span className={styles.stackedLabel} aria-hidden="true">
		{children}
	</span>
);

/** Public or Private, as an icon plus the word, so it never relies on the icon alone. */
export const VisibilityCell: React.FC<{ publicity: number }> = ({ publicity }) => {
	const visibility = visibilityDisplay(publicity);

	return (
		<span className={styles.visibility}>
			<Icon name={visibility.icon} size="sm" />
			{visibility.label}
		</span>
	);
};

export const DateCell: React.FC<{ iso: string }> = ({ iso }) => (
	<time dateTime={iso} className={styles.date}>
		{formatDashboardDate(iso)}
	</time>
);

export interface RowActionsProps {
	/** What the row is called, for the action names ("Edit 記事", "Delete 記事"). */
	name: string;
	editTo: string;
	onDelete: () => void;
}

/** Edit (a link to the owner's form) and Delete (the page confirms first). */
export const RowActions: React.FC<RowActionsProps> = ({ name, editTo, onDelete }) => (
	<span className={styles.actionGroup}>
		<Button to={editTo} variant="ghost" size="sm" hasOnlyIcon aria-label={`Edit ${name}`}>
			<Icon name="penSolid" size="sm" />
		</Button>
		<Button variant="ghost" size="sm" hasOnlyIcon aria-label={`Delete ${name}`} onClick={onDelete}>
			<Icon name="trashbinSolid" size="sm" />
		</Button>
	</span>
);

/** Class for a column of status pills, so a pill never breaks inside its label. */
export const statusCellClassName = styles.statusCell;

/** Class for the actions column's cells: right-aligned in the table, bottom-right when stacked. */
export const actionsCellClassName = styles.actionsCell;

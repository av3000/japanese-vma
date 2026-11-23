import * as React from 'react';
import { formatCount } from '@/components/features/dashboard/dashboardValues';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Cluster, Stack } from '@/components/shared/layout';
import styles from './Dashboard.module.css';

/** Page size for the dashboard tables, matching the dictionary tables (UI-DICT-00, #427). */
export const DASHBOARD_PER_PAGE = 25;

/** The part of an infinite query result the section reads; every `useInfinite*` hook returns it. */
export interface DashboardListQuery {
	total: number;
	isPending: boolean;
	isError: boolean;
	hasNextPage: boolean;
	isFetchingNextPage: boolean;
	fetchNextPage: () => unknown;
	refetch: () => unknown;
}

interface DashboardListSectionProps {
	/** Plural noun for the count line: "articles", "lists". */
	noun: string;
	/** Subject of the error message: "Your articles". */
	subject: string;
	/** How many rows are loaded across all pages. */
	itemCount: number;
	query: DashboardListQuery;
	filters?: React.ReactNode;
	/** One message above the table, e.g. a failed delete. */
	notice?: React.ReactNode;
	/** The count line takes focus after a delete, since the row that had it is gone. */
	summaryRef?: React.Ref<HTMLParagraphElement>;
	/** Renders the table. `loading` is true until the first page arrives. */
	children: (view: { loading: boolean }) => React.ReactNode;
}

/**
 * One dashboard tab's list: filters, the count line, the table, and Load more. A failed first
 * load shows a fixed message with a retry, never the raw error; a failed background refetch keeps
 * the rows that are already on screen.
 */
export const DashboardListSection: React.FC<DashboardListSectionProps> = ({
	noun,
	subject,
	itemCount,
	query,
	filters,
	notice,
	summaryRef,
	children,
}) => {
	const { total, isPending, isError, hasNextPage, isFetchingNextPage, fetchNextPage, refetch } = query;
	const failed = isError && itemCount === 0;

	return (
		<Stack gap="md">
			{filters}
			{notice}
			{failed ? (
				<Alert
					tone="danger"
					actions={
						<Button variant="secondary-outline" onClick={() => void refetch()}>
							Try again
						</Button>
					}
				>
					{subject} could not be loaded.
				</Alert>
			) : (
				<>
					{isPending ? null : (
						<p ref={summaryRef} tabIndex={-1} className={styles.summary}>
							{total === 0
								? `0 ${noun}`
								: `Showing ${formatCount(itemCount)} of ${formatCount(total)} ${noun}`}
						</p>
					)}
					{children({ loading: isPending })}
					{isPending || itemCount === 0 ? null : (
						<Cluster justify="center" className={styles.loadMoreRow}>
							{hasNextPage ? (
								<Button
									variant="secondary-outline"
									className={styles.loadMore}
									onClick={() => void fetchNextPage()}
									disabled={isFetchingNextPage}
								>
									{isFetchingNextPage ? 'Loading…' : 'Load more'}
								</Button>
							) : (
								<span className={styles.muted}>No more results</span>
							)}
						</Cluster>
					)}
				</>
			)}
		</Stack>
	);
};

export default DashboardListSection;

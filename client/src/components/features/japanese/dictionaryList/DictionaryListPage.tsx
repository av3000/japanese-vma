import * as React from 'react';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import type { DataTableEmpty } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import styles from './DictionaryList.module.css';

/** Page size for the four dictionary lists: tables fit about 25 rows in 1.5 screens (UI-DICT-00, #427). */
export const DICTIONARY_PER_PAGE = 25;

const countFormat = new Intl.NumberFormat('en-US');

/** "Showing 25 of 13,108", or "0 results": the count line every dictionary list starts its meta with. */
const showingCount = (shown: number, total: number) =>
	total === 0 ? '0 results' : `Showing ${countFormat.format(shown)} of ${countFormat.format(total)}`;

/** The shared "empty search" wording for the table's empty panel. */
const emptySearch = (plural: string, keyword: string, hasOtherFilters: boolean): DataTableEmpty => ({
	title:
		keyword !== '' ? (
			<>
				No {plural} match “<span lang="ja">{keyword}</span>”
			</>
		) : hasOtherFilters ? (
			`No ${plural} match these filters`
		) : (
			`No ${plural} yet`
		),
	hint:
		keyword !== '' || hasOtherFilters
			? 'Try a shorter search, a reading in kana, or clear the filters.'
			: undefined,
});

/** The part of an infinite query result the page reads; every `useInfinite*` hook returns it. */
export interface DictionaryListQuery {
	total: number;
	isPending: boolean;
	isError: boolean;
	hasNextPage: boolean;
	isFetchingNextPage: boolean;
	fetchNextPage: () => unknown;
	refetch: () => unknown;
}

interface DictionaryListPageProps {
	/** Heading and the subject of the error message: "Kanji", "Words". */
	title: string;
	/** Plural noun for the empty panel: "kanji", "words". */
	noun: string;
	/** The applied keyword, already trimmed. */
	keyword: string;
	/** Further active filters for the meta line, e.g. `JLPT: N3`. */
	extraMeta?: readonly string[];
	/** Whether a filter other than the keyword is active, so an empty result words itself right. */
	hasOtherFilters?: boolean;
	/** How many rows are loaded across all pages. */
	itemCount: number;
	query: DictionaryListQuery;
	action?: React.ReactNode;
	filters: React.ReactNode;
	/** Renders the table. `loading` is true until the first page arrives. */
	children: (view: { loading: boolean; empty: DataTableEmpty }) => React.ReactNode;
}

/**
 * The Kanji, Words, Sentences and Radicals lists as one page: header with the count line, filters,
 * the table, and Load more. It owns the loading and error policy so the four routes cannot drift:
 *
 * - loading is "no page yet" (`isPending`), shown as skeleton rows under the real header;
 * - a failed load shows a fixed message with a retry, never the raw error text, and only while
 *   there is nothing to show: a failed background refetch or Load more keeps the loaded rows.
 */
export const DictionaryListPage: React.FC<DictionaryListPageProps> = ({
	title,
	noun,
	keyword,
	extraMeta = [],
	hasOtherFilters = false,
	itemCount,
	query,
	action,
	filters,
	children,
}) => {
	const { total, isPending, isError, hasNextPage, isFetchingNextPage, fetchNextPage, refetch } = query;
	const loading = isPending;
	const failed = isError && itemCount === 0;
	const meta =
		loading || failed
			? undefined
			: [showingCount(itemCount, total), keyword !== '' && `keyword: ${keyword}`, ...extraMeta]
					.filter(Boolean)
					.join(' · ');

	return (
		<Container className={styles.page}>
			<Stack gap="md">
				<PageHeader title={title} meta={meta} action={action} />
				{filters}
				{failed ? (
					<Alert
						tone="danger"
						actions={
							<Button variant="secondary-outline" onClick={() => void refetch()}>
								Try again
							</Button>
						}
					>
						{title} could not be loaded.
					</Alert>
				) : (
					<>
						{children({ loading, empty: emptySearch(noun, keyword, hasOtherFilters) })}
						{loading || itemCount === 0 ? null : (
							<LoadMore
								hasNextPage={hasNextPage}
								isFetchingNextPage={isFetchingNextPage}
								onLoadMore={() => void fetchNextPage()}
							/>
						)}
					</>
				)}
			</Stack>
		</Container>
	);
};

interface LoadMoreProps {
	hasNextPage: boolean;
	isFetchingNextPage: boolean;
	onLoadMore: () => void;
}

const LoadMore: React.FC<LoadMoreProps> = ({ hasNextPage, isFetchingNextPage, onLoadMore }) => (
	<Cluster justify="center" className={styles.loadMoreRow}>
		{hasNextPage ? (
			<Button
				variant="secondary-outline"
				className={styles.loadMore}
				onClick={onLoadMore}
				disabled={isFetchingNextPage}
			>
				{isFetchingNextPage ? 'Loading…' : 'Load more'}
			</Button>
		) : (
			<span className={styles.muted}>No more results</span>
		)}
	</Cluster>
);

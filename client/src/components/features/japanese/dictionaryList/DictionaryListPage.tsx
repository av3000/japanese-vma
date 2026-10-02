import * as React from 'react';
import { Button } from '@/components/shared/Button';
import { PageHeader } from '@/components/shared/PageHeader';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import styles from './DictionaryList.module.css';

interface DictionaryListPageProps {
	title: string;
	/** Count and active-search line; leave it out while the first page loads. */
	meta?: React.ReactNode;
	action?: React.ReactNode;
	filters: React.ReactNode;
	children: React.ReactNode;
}

/** Page shell shared by the Kanji, Words, Sentences and Radicals lists: header, filters, results. */
export const DictionaryListPage: React.FC<DictionaryListPageProps> = ({ title, meta, action, filters, children }) => (
	<Container className={styles.page}>
		<Stack gap="md">
			<PageHeader title={title} meta={meta} action={action} />
			{filters}
			{children}
		</Stack>
	</Container>
);

interface LoadMoreProps {
	hasNextPage: boolean;
	isFetchingNextPage: boolean;
	onLoadMore: () => void;
}

export const LoadMore: React.FC<LoadMoreProps> = ({ hasNextPage, isFetchingNextPage, onLoadMore }) => (
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

/** Page size for the four dictionary lists: tables fit about 25 rows in 1.5 screens (UI-DICT-00, #427). */
export const DICTIONARY_PER_PAGE = 25;

const countFormat = new Intl.NumberFormat('en-US');

/** "Showing 25 of 13,108", or "0 results": the count line every dictionary list starts its meta with. */
export const showingCount = (shown: number, total: number) =>
	total === 0 ? '0 results' : `Showing ${countFormat.format(shown)} of ${countFormat.format(total)}`;

/** The shared "empty search" wording for the table's empty panel. */
export const emptySearch = (plural: string, keyword: string, hasOtherFilters = false) => ({
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

import * as React from 'react';
import type { PagedIndex } from '@/api/pagedIndex';
import { Button } from '@/components/shared/Button';
import { Pagination } from '@/components/shared/Pagination';
import styles from './DictionaryList.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

/** "Showing 21–40 of 171", or "Showing all 171" once every page is loaded. */
export const pagedSummary = (
	list: Pick<PagedIndex<unknown>, 'isAll' | 'page' | 'perPage' | 'rows' | 'total'>,
	noun: string,
) => {
	if (list.total === 0) return null;
	if (list.isAll) return `Showing all ${numberFormat.format(list.total)} ${noun}`;

	const first = (list.page - 1) * list.perPage + 1;
	const last = first + list.rows.length - 1;

	return `Showing ${numberFormat.format(first)}–${numberFormat.format(last)} of ${numberFormat.format(list.total)} ${noun}`;
};

export interface PagedControlsProps {
	list: PagedIndex<unknown>;
	/** Plural noun, e.g. "kanji" or "words". */
	noun: string;
	/** Accessible name of the page navigation, e.g. "Kanji pages". */
	label: string;
}

/**
 * The controls under a paged table: numbered pages and "Load all", or, once everything is loaded,
 * "Show pages". Loading all reports progress in a polite status; a failure says so in plain words.
 */
export const PagedControls: React.FC<PagedControlsProps> = ({ list, noun, label }) => {
	const summary = pagedSummary(list, noun);

	return (
		<div className={styles.pagedControls}>
			<p className={styles.pagedStatus} role="status">
				{list.isError
					? `The ${noun} could not be loaded. Try again in a moment.`
					: list.progress
						? `Loading ${numberFormat.format(list.progress.loaded)} of ${numberFormat.format(list.progress.total)} ${noun}…`
						: summary}
			</p>
			{list.isAll ? (
				<Button variant="outline" size="sm" onClick={list.showPages} disabled={list.isFetching}>
					Show pages
				</Button>
			) : (
				<>
					<Pagination
						page={list.page}
						pageCount={list.pageCount}
						onPageChange={list.setPage}
						label={label}
					/>
					{list.pageCount > 1 ? (
						<Button variant="outline" size="sm" onClick={list.loadAll}>
							{`Load all ${numberFormat.format(list.total)} ${noun}`}
						</Button>
					) : null}
				</>
			)}
		</div>
	);
};

export default PagedControls;

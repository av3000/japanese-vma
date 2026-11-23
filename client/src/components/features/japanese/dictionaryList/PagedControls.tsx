import * as React from 'react';
import type { PagedIndex } from '@/api/pagedIndex';
import { FilterBar } from '@/components/shared/FilterBar';
import { Pagination } from '@/components/shared/Pagination';
import styles from './DictionaryList.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

/** "Showing 21–40 of 171 kanji". */
export const pagedSummary = (list: Pick<PagedIndex<unknown>, 'page' | 'perPage' | 'rows' | 'total'>, noun: string) => {
	if (list.total === 0 || list.rows.length === 0) return null;

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

/** Under a paged table: what is shown, and the numbered pages. A failure says so in plain words. */
export const PagedControls: React.FC<PagedControlsProps> = ({ list, noun, label }) => (
	<div className={styles.pagedControls}>
		<p className={styles.pagedStatus} role="status">
			{list.isError ? `The ${noun} could not be loaded. Try again in a moment.` : pagedSummary(list, noun)}
		</p>
		<Pagination page={list.page} pageCount={list.pageCount} onPageChange={list.setPage} label={label} />
	</div>
);

export interface PagedListFiltersProps {
	list: PagedIndex<unknown>;
	/** Plural noun, e.g. "kanji". */
	noun: string;
	/** Placeholder of the search input, e.g. "Kanji, reading or meaning". */
	placeholder: string;
}

/**
 * Above a paged table: a keyword search, applied on Enter or the button, and the page size. Both go
 * back to page 1. The typed draft is local; the applied keyword lives in the list.
 */
export const PagedListFilters: React.FC<PagedListFiltersProps> = ({ list, noun, placeholder }) => {
	const [draft, setDraft] = React.useState(list.keyword);
	const sizes = list.pageSizes.map((size) => ({ value: String(size), label: `${size} per page` }));

	return (
		<FilterBar onSubmit={() => list.setKeyword(draft)} label={`Search ${noun}`}>
			<FilterBar.Search
				label={`Search ${noun}`}
				placeholder={placeholder}
				name="keyword"
				value={draft}
				onChange={setDraft}
			/>
			<FilterBar.Filters>
				<FilterBar.Select
					label="Per page"
					value={String(list.perPage)}
					options={sizes}
					onChange={(value) => list.setPerPage(Number(value))}
				/>
			</FilterBar.Filters>
			<FilterBar.Reset
				active={list.keyword !== ''}
				onClick={() => {
					setDraft('');
					list.setKeyword('');
				}}
			/>
		</FilterBar>
	);
};

export default PagedControls;

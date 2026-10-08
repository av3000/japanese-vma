import * as React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import styles from './Pagination.module.css';

export type PageItem = number | 'gap';

/**
 * The page buttons to show: always the first and last page and the current page with its
 * neighbours, with a gap where pages are skipped. Short runs show every page, because a gap that
 * hides a single page saves no space.
 */
export const pageItems = (page: number, pageCount: number): PageItem[] => {
	if (pageCount <= 7) return Array.from({ length: pageCount }, (_, index) => index + 1);

	const current = Math.min(Math.max(page, 1), pageCount);
	const start = Math.max(2, current - 1);
	const end = Math.min(pageCount - 1, current + 1);
	const items: PageItem[] = [1];

	if (start > 3) items.push('gap');
	else for (let n = 2; n < start; n += 1) items.push(n);

	for (let n = start; n <= end; n += 1) items.push(n);

	if (end < pageCount - 2) items.push('gap');
	else for (let n = end + 1; n < pageCount; n += 1) items.push(n);

	items.push(pageCount);

	return items;
};

export interface PaginationProps {
	page: number;
	pageCount: number;
	onPageChange: (page: number) => void;
	/** Accessible name of the navigation, e.g. "Kanji pages". */
	label: string;
	disabled?: boolean;
	className?: string;
}

/**
 * Numbered page navigation: previous, the page buttons from `pageItems`, next. The current page is
 * marked with `aria-current="page"` as well as its style. Renders nothing for a single page.
 */
export const Pagination: React.FC<PaginationProps> = ({
	page,
	pageCount,
	onPageChange,
	label,
	disabled = false,
	className,
}) => {
	if (pageCount <= 1) return null;

	return (
		<nav className={classNames(styles.pagination, className)} aria-label={label}>
			<ul className={styles.list}>
				<li>
					<Button
						variant="outline"
						size="sm"
						disabled={disabled || page <= 1}
						onClick={() => onPageChange(page - 1)}
						aria-label="Previous page"
					>
						Previous
					</Button>
				</li>
				{pageItems(page, pageCount).map((item, index) =>
					item === 'gap' ? (
						<li key={`gap-${index}`} className={styles.gap} aria-hidden="true">
							…
						</li>
					) : (
						<li key={item}>
							<Button
								variant={item === page ? 'primary' : 'ghost'}
								size="sm"
								disabled={disabled}
								onClick={() => item !== page && onPageChange(item)}
								aria-label={`Page ${item}`}
								aria-current={item === page ? 'page' : undefined}
								className={styles.page}
							>
								{item}
							</Button>
						</li>
					),
				)}
				<li>
					<Button
						variant="outline"
						size="sm"
						disabled={disabled || page >= pageCount}
						onClick={() => onPageChange(page + 1)}
						aria-label="Next page"
					>
						Next
					</Button>
				</li>
			</ul>
		</nav>
	);
};

export default Pagination;

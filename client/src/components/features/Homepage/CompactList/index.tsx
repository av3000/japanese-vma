import * as React from 'react';
import { Link, type LinkProps } from 'react-router-dom';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import styles from './CompactList.module.css';

export const COMPACT_LIST_ROWS = 4;

export type CompactListStatus = 'pending' | 'error' | 'success';

export interface CompactListSectionProps {
	title: string;
	/** Where "All …" goes. */
	allHref: string;
	/** Noun for the "All" link, e.g. "articles": the link reads "All 128" and is named "All 128 articles". */
	allNoun: string;
	/** Known once loaded; the link reads "All {noun}" until then. */
	total?: number;
	status: CompactListStatus;
	isEmpty: boolean;
	emptyText: string;
	errorText: string;
	onRetry: () => void;
	/** The `<li>` rows (see `CompactListRow`). */
	children?: React.ReactNode;
	className?: string;
}

/**
 * Shell shared by the landing page's compact lists: a heading row with an "All" link, then one
 * of four states. Loading shows skeleton rows of the final height; errors never show the raw
 * message, only a fixed line and a Retry button.
 */
export const CompactListSection: React.FC<CompactListSectionProps> = ({
	title,
	allHref,
	allNoun,
	total,
	status,
	isEmpty,
	emptyText,
	errorText,
	onRetry,
	children,
	className,
}) => {
	const headingId = React.useId();
	const hasTotal = status === 'success' && total !== undefined;

	return (
		<section className={classNames(styles.section, className)} aria-labelledby={headingId}>
			<div className={styles.header}>
				<h2 id={headingId} className={styles.title}>
					{title}
				</h2>
				<Link
					to={allHref}
					className={styles.allLink}
					aria-label={hasTotal ? `All ${total} ${allNoun}` : undefined}
				>
					{hasTotal ? `All ${total}` : `All ${allNoun}`}
				</Link>
			</div>

			{status === 'pending' && (
				<ul className={styles.list} aria-hidden="true">
					{Array.from({ length: COMPACT_LIST_ROWS }, (_, index) => (
						<li key={index} className={styles.row} data-testid="compact-list-skeleton">
							<span className={classNames(styles.skeleton, styles.skeletonTitle)} />
							<span className={classNames(styles.skeleton, styles.skeletonMeta)} />
						</li>
					))}
				</ul>
			)}

			{status === 'error' && (
				<div className={styles.message}>
					<p>{errorText}</p>
					<Button type="button" variant="outline" size="sm" onClick={onRetry}>
						Retry
					</Button>
				</div>
			)}

			{status === 'success' &&
				(isEmpty ? <p className={styles.message}>{emptyText}</p> : <ul className={styles.list}>{children}</ul>)}
		</section>
	);
};

/** A list row; the row's primary link covers the whole row (see `CompactListLink`). */
export const CompactListRow: React.FC<React.LiHTMLAttributes<HTMLLIElement>> = ({ className, ...rest }) => (
	<li className={classNames(styles.row, className)} {...rest} />
);

/**
 * The row's one link. Its hit area is stretched over the row, so the whole row is clickable while
 * the link's accessible name stays just the title.
 */
export const CompactListLink: React.FC<LinkProps> = ({ className, ...rest }) => (
	<Link className={classNames(styles.rowLink, className)} {...rest} />
);

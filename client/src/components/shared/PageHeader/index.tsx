import * as React from 'react';
import classNames from 'classnames';
import styles from './PageHeader.module.css';

export interface PageHeaderProps {
	/** The page's only `<h1>`. */
	title: string;
	/** One muted line under the title for counts and active-search context. The caller formats it. */
	meta?: React.ReactNode;
	/** One primary action, usually a `Button`. */
	action?: React.ReactNode;
	className?: string;
}

/**
 * Heading for a page or list: title, an optional primary action beside it, and one line of meta
 * below. Makes no data requests; while a page is loading, render it with the title and no `meta`.
 */
export const PageHeader: React.FC<PageHeaderProps> = ({ title, meta, action, className }) => (
	<header className={classNames(styles.header, className)}>
		<div className={styles.row}>
			<h1 className={styles.title}>{title}</h1>
			{action ? <div className={styles.action}>{action}</div> : null}
		</div>
		{meta ? <p className={styles.meta}>{meta}</p> : null}
	</header>
);

export default PageHeader;

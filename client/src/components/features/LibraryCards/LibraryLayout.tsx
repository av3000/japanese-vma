import * as React from 'react';
import classNames from 'classnames';
import { Container, Stack } from '@/components/shared/layout';
import { japaneseLang } from '@/helpers/japaneseLang';
import styles from './LibraryLayout.module.css';

/** The grey Library page: header, filters, card grid and Load More stack inside it. */
export const LibraryPage: React.FC<{ children: React.ReactNode }> = ({ children }) => (
	<div className={styles.page}>
		<Container className={styles.content}>
			<Stack gap="md">{children}</Stack>
		</Container>
	</div>
);

/** The card grid. Each child becomes one list item. */
export const LibraryCardGrid: React.FC<{
	children: React.ReactNode;
	className?: string;
	'aria-hidden'?: boolean;
}> = ({ children, className, ...rest }) => (
	<ul className={classNames(styles.grid, className)} {...rest}>
		{React.Children.map(children, (child) => (
			<li className={styles.gridItem}>{child}</li>
		))}
	</ul>
);

export interface LibraryEmptyStateProps {
	title: string;
	/** The search that found nothing, quoted after the title and marked as Japanese when it is. */
	term?: string;
	hint: string;
}

export const LibraryEmptyState: React.FC<LibraryEmptyStateProps> = ({ title, term, hint }) => (
	<div className={styles.empty} data-testid="library-empty-state">
		<h2 className={styles.emptyTitle}>
			{title}
			{term && (
				<>
					{' '}
					“<span lang={japaneseLang(term)}>{term}</span>”
				</>
			)}
		</h2>
		<p className={styles.emptyHint}>{hint}</p>
	</div>
);

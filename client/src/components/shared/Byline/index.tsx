import * as React from 'react';
import classNames from 'classnames';
import { Initials } from '@/components/shared/Initials';
import { formatDate } from '@/helpers/date';
import styles from './Byline.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

export const viewsLabel = (views: number): string => `${numberFormat.format(views)} ${views === 1 ? 'view' : 'views'}`;

export interface BylineProps {
	/** The author or owner. Ignored when `source` is given. */
	name?: string | null;
	/** An Imported Article's source: the byline credits it instead of the account that imported it. */
	source?: string | null;
	/** ISO date the item was created. */
	date?: string | null;
	views?: number | null;
	className?: string;
}

const Separator = () => (
	<span className={styles.separator} aria-hidden="true">
		·
	</span>
);

/**
 * One line under a detail page's title: who wrote it, when, and how often it was read.
 * "by Name" with initials for people, "from Source" for Imported Articles.
 */
export const Byline: React.FC<BylineProps> = ({ name, source, date, views, className }) => {
	const formattedDate = date ? formatDate(date) : null;

	return (
		<p className={classNames(styles.byline, className)}>
			{source ? (
				<span className={styles.who}>
					from <strong className={styles.name}>{source}</strong>
				</span>
			) : (
				<span className={styles.who}>
					<Initials name={name} size="sm" />
					<span>
						by <strong className={styles.name}>{name || 'Unknown author'}</strong>
					</span>
				</span>
			)}
			{formattedDate && date ? (
				<>
					<Separator />
					<time dateTime={date}>{formattedDate}</time>
				</>
			) : null}
			{typeof views === 'number' ? (
				<>
					<Separator />
					<span className={styles.views}>{viewsLabel(views)}</span>
				</>
			) : null}
		</p>
	);
};

export default Byline;

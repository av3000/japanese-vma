import * as React from 'react';
import classNames from 'classnames';
import styles from './DetailActions.module.css';

export interface DetailActionsProps {
	children: React.ReactNode;
	className?: string;
}

/**
 * The rail's actions, one under the other at full width. Pass shared `Button`s with
 * `variant="outline"` and `isFullWidth`, so pressed, expanded and loading states stay the Button's
 * own. It holds no data, mutations or dialogs: the page keeps its modal controllers.
 */
export const DetailActions: React.FC<DetailActionsProps> = ({ children, className }) => (
	<div className={classNames(styles.actions, className)}>{children}</div>
);

export interface DetailActionGroupProps {
	/** E.g. "Your article" or "Moderation". */
	heading: string;
	headingLevel?: 2 | 3;
	/** Shown under the heading before the actions, e.g. the current `StatusPill`. */
	meta?: React.ReactNode;
	children: React.ReactNode;
	className?: string;
}

/** A titled group of actions inside `DetailActions`, for owner and moderator controls. */
export const DetailActionGroup: React.FC<DetailActionGroupProps> = ({
	heading,
	headingLevel = 2,
	meta,
	children,
	className,
}) => {
	const headingId = React.useId();
	const Heading = `h${headingLevel}` as const;

	return (
		<section className={classNames(styles.group, className)} aria-labelledby={headingId}>
			<Heading id={headingId} className={styles.heading}>
				{heading}
			</Heading>
			{meta ? <div className={styles.meta}>{meta}</div> : null}
			<div className={styles.actions}>{children}</div>
		</section>
	);
};

export default DetailActions;

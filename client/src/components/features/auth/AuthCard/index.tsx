import * as React from 'react';
import classNames from 'classnames';
import { Stack } from '@/components/shared/layout';
import styles from './AuthCard.module.css';

export interface AuthCardProps {
	/** The page's only `<h1>`. Also names the section landmark. */
	title: string;
	/** One muted line under the title. */
	intro?: React.ReactNode;
	/** The general error or the session-expired warning, shown between the heading and the form. */
	alert?: React.ReactNode;
	/** Bottom row, usually the link to the other auth page. */
	footer?: React.ReactNode;
	/** The form. */
	children: React.ReactNode;
	className?: string;
}

/**
 * Library Cards shell for Login and Register: a bordered card centred on a grey band. Text is
 * start-aligned, and the band is top-aligned so the card does not jump when errors appear.
 */
export const AuthCard: React.FC<AuthCardProps> = ({ title, intro, alert, footer, children, className }) => {
	const titleId = React.useId();

	return (
		<section className={classNames(styles.page, className)} aria-labelledby={titleId}>
			<Stack gap="lg" className={styles.card}>
				<Stack as="header" gap="2xs">
					<h1 id={titleId} className={styles.title}>
						{title}
					</h1>
					{intro ? <p className={styles.intro}>{intro}</p> : null}
				</Stack>
				{alert ?? null}
				{children}
				{footer ? <footer className={styles.footer}>{footer}</footer> : null}
			</Stack>
		</section>
	);
};

export default AuthCard;

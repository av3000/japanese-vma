import * as React from 'react';
import classNames from 'classnames';
import { Link } from '@/components/shared/Link';
import { Container, Stack } from '@/components/shared/layout';
import styles from './FormPage.module.css';

/* FormPage --------------------------------------------------------------- */

export interface FormPageProps {
	/** The page's only `<h1>`. Also names the section landmark. */
	title: string;
	/** One muted line under the title. */
	intro?: React.ReactNode;
	/** Link above the heading, back to where the form was opened from. */
	backLink?: { to: string; label: string };
	/** `md` for two-column forms; `sm` for single-card forms, so the heading lines up with the card. */
	size?: 'sm' | 'md';
	children: React.ReactNode;
	className?: string;
}

/**
 * Library Cards page for create and edit forms: a grey band with the heading, then the form's
 * cards. The form itself lays out its cards with `FormLayout`, so the same form also works in a
 * modal without the page around it.
 */
export const FormPage: React.FC<FormPageProps> = ({ title, intro, backLink, size = 'md', children, className }) => {
	const titleId = React.useId();

	return (
		<section className={classNames(styles.page, className)} aria-labelledby={titleId}>
			<Container size={size} className={styles.content}>
				<Stack gap="lg">
					<Stack as="header" gap="2xs">
						{backLink ? (
							<Link to={backLink.to} className={styles.back}>
								<span aria-hidden="true">←</span>
								{backLink.label}
							</Link>
						) : null}
						<h1 id={titleId} className={styles.title}>
							{title}
						</h1>
						{intro ? <p className={styles.intro}>{intro}</p> : null}
					</Stack>
					{children}
				</Stack>
			</Container>
		</section>
	);
};

/* FormLayout ------------------------------------------------------------- */

export interface FormLayoutProps {
	/** One general alert, above the cards. */
	alert?: React.ReactNode;
	/** The settings column: beside the main column from 1024px, after it below that. */
	aside?: React.ReactNode;
	/** Submit and Cancel. */
	actions: React.ReactNode;
	/** One column at every width, for forms inside a modal. */
	stacked?: boolean;
	/** The main column, usually one `FormCard`. */
	children: React.ReactNode;
	className?: string;
}

/**
 * Arranges a form's cards. DOM order is the visual order (alert, content, settings, actions), so
 * keyboard order follows what the reader sees at every width.
 */
export const FormLayout: React.FC<FormLayoutProps> = ({ alert, aside, actions, stacked, children, className }) => (
	<div className={classNames(styles.layout, Boolean(aside) && !stacked && styles.withAside, className)}>
		{alert ? <div className={styles.alert}>{alert}</div> : null}
		<Stack gap="md" className={styles.main}>
			{children}
		</Stack>
		{aside ? (
			<Stack gap="md" className={styles.aside}>
				{aside}
			</Stack>
		) : null}
		<div className={styles.actions}>{actions}</div>
	</div>
);

/* FormCard --------------------------------------------------------------- */

export interface FormCardProps {
	/** Rendered as an `<h2>`, e.g. "Settings". */
	title?: string;
	children: React.ReactNode;
	className?: string;
}

export const FormCard: React.FC<FormCardProps> = ({ title, children, className }) => {
	const titleId = React.useId();

	return (
		<Stack
			as="section"
			gap="md"
			className={classNames(styles.card, className)}
			aria-labelledby={title ? titleId : undefined}
		>
			{title ? (
				<h2 id={titleId} className={styles.cardTitle}>
					{title}
				</h2>
			) : null}
			{children}
		</Stack>
	);
};

/* FormNote --------------------------------------------------------------- */

export interface FormNoteProps {
	/** Short heading, e.g. "What happens next". */
	title: string;
	children: React.ReactNode;
	className?: string;
}

/** What happens after the form is sent. Muted, and named by its heading rather than a colour. */
export const FormNote: React.FC<FormNoteProps> = ({ title, children, className }) => {
	const titleId = React.useId();

	return (
		<Stack as="aside" gap="2xs" className={classNames(styles.note, className)} aria-labelledby={titleId}>
			<p id={titleId} className={styles.noteTitle}>
				{title}
			</p>
			<div className={styles.noteBody}>{children}</div>
		</Stack>
	);
};

export default FormPage;

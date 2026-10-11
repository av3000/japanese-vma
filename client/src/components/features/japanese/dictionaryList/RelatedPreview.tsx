import * as React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { DialogModal } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';
import styles from './RelatedPreview.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

export interface RelatedSectionProps {
	title: string;
	/** Shown beside the title when above 0. */
	count?: number;
	/** 3 under a section that has its own `h2` (Article detail); 2 directly under the page `h1`. */
	headingLevel?: 2 | 3;
	children: React.ReactNode;
}

/** A related list's section: its heading with an optional count, then the list. */
export const RelatedSection: React.FC<RelatedSectionProps> = ({ title, count, headingLevel = 2, children }) => {
	const headingId = React.useId();
	const Heading = `h${headingLevel}` as const;

	return (
		<section className={styles.section} aria-labelledby={headingId}>
			<Heading
				className={classNames(styles.heading, headingLevel === 2 ? styles.headingTop : styles.headingNested)}
				id={headingId}
			>
				{title}
				{count !== undefined && count > 0 ? (
					<span className={styles.count}>{numberFormat.format(count)}</span>
				) : null}
			</Heading>
			{children}
		</section>
	);
};

export interface RelatedPreviewProps {
	title: string;
	/** Plural noun for the loading line and the button, e.g. "words": "See all 2,398 words". */
	noun: string;
	total: number;
	isPending: boolean;
	isError: boolean;
	isEmpty: boolean;
	emptyMessage: string;
	/** The modal "See all" opens. */
	controller: ModalController;
	/** 3 under a section that has its own `h2` (Article detail); 2 directly under the page `h1`. */
	headingLevel?: 2 | 3;
	/** The first few rows. */
	children: React.ReactNode;
}

/**
 * A short preview of a related list under its heading and count, with "See all N …" opening the
 * full list in a modal (#522). While loading, on error and when empty it shows one fixed line;
 * server text never reaches the page.
 */
export const RelatedPreview: React.FC<RelatedPreviewProps> = ({
	title,
	noun,
	total,
	isPending,
	isError,
	isEmpty,
	emptyMessage,
	controller,
	headingLevel = 2,
	children,
}) => (
	<RelatedSection title={title} count={!isPending && !isError ? total : undefined} headingLevel={headingLevel}>
		{isPending ? <p className={styles.message}>Loading {noun}…</p> : null}
		{isError ? (
			<p className={styles.message} role="status">
				{title} could not be loaded. Reload the page to try again.
			</p>
		) : null}
		{!isPending && !isError && isEmpty ? <p className={styles.message}>{emptyMessage}</p> : null}
		{!isPending && !isError && !isEmpty ? (
			<>
				{children}
				<Button
					className={styles.seeAll}
					variant="outline"
					aria-controls={controller.id}
					aria-expanded={controller.isOpen}
					onClick={controller.open}
				>
					{`See all ${numberFormat.format(total)} ${noun}`}
				</Button>
			</>
		) : null}
	</RelatedSection>
);

export interface RelatedListModalProps {
	controller: ModalController;
	title: string;
	/** Usually `PagedListFilters`, a dictionary table and `PagedControls`. */
	children: React.ReactNode;
}

/** The full related list in an `xl` dialog, full screen below 768px. Rendered only once opened. */
export const RelatedListModal: React.FC<RelatedListModalProps> = ({ controller, title, children }) => {
	const titleId = React.useId();

	if (!controller.isRendered) return null;

	return (
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			size="xl"
			ariaLabel={title}
		>
			<DialogModal.Header>
				<DialogModal.Title id={titleId}>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>
				<div className={styles.modalBody}>{children}</div>
			</DialogModal.Body>
		</DialogModal>
	);
};

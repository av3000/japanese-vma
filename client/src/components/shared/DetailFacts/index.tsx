import * as React from 'react';
import classNames from 'classnames';
import styles from './DetailFacts.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

/**
 * Characters in a text, counted by code point and without whitespace, so line breaks and spacing
 * between paragraphs do not inflate the length of a Japanese text.
 */
export const characterCount = (text: string | null | undefined): number =>
	Array.from((text ?? '').replace(/\s+/gu, '')).length;

/** A value that is not a count, e.g. a JLPT badge, "Grade 1" or a linked radical. */
export interface DetailFactText {
	text: React.ReactNode;
}

export interface DetailFact {
	term: string;
	/**
	 * A count, `null` while it is still being worked out ("Counting…"), or `{ text }` for a value
	 * that is not a number. Text is shown as given, without number formatting.
	 */
	value: number | null | DetailFactText;
}

const factValue = (value: DetailFact['value']): React.ReactNode => {
	if (value === null) return 'Counting…';
	if (typeof value === 'number') return numberFormat.format(value);

	return value.text;
};

export interface DetailFactsProps {
	/** Eyebrow heading, e.g. "In this reading". */
	title: string;
	headingLevel?: 2 | 3;
	facts: readonly DetailFact[];
	/** Under a rule below the list, e.g. a `JlptBar`. */
	children?: React.ReactNode;
	className?: string;
}

/** The rail's facts card: a titled `<dl>` of counts and short values, with an optional footer such as a `JlptBar`. */
export const DetailFacts: React.FC<DetailFactsProps> = ({ title, headingLevel = 2, facts, children, className }) => {
	const headingId = React.useId();
	const Heading = `h${headingLevel}` as const;
	const isCounting = facts.some((fact) => fact.value === null);

	return (
		<section className={classNames(styles.card, className)} aria-labelledby={headingId}>
			<Heading id={headingId} className={styles.title}>
				{title}
			</Heading>
			<dl className={styles.list} aria-busy={isCounting || undefined}>
				{facts.map((fact) => (
					<div key={fact.term} className={styles.row}>
						<dt className={styles.term}>{fact.term}</dt>
						<dd
							className={classNames(
								styles.value,
								fact.value === null && styles.pending,
								fact.value !== null && typeof fact.value === 'object' && styles.text,
							)}
						>
							{factValue(fact.value)}
						</dd>
					</div>
				))}
			</dl>
			{children ? <div className={styles.footer}>{children}</div> : null}
		</section>
	);
};

export default DetailFacts;

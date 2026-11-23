import * as React from 'react';
import { Link } from 'react-router-dom';
import { LevelBadge } from '@/components/shared/LevelBadge';
import styles from './DictionaryList.module.css';
import { toJlptLevel } from './listValues';

/** A dash for a missing value, announced as `label` ("No on'yomi"). */
export const Missing: React.FC<{ label: string }> = ({ label }) => (
	<span className={styles.missing}>
		<span aria-hidden="true">—</span>
		<span className={styles.visuallyHidden}>{label}</span>
	</span>
);

/** The detail link for a kanji or radical: one large serif glyph. */
export const GlyphLink: React.FC<{ to: string; glyph: string | null; missingLabel: string }> = ({
	to,
	glyph,
	missingLabel,
}) => (
	<Link to={to} className={styles.glyph} lang={glyph ? 'ja' : undefined}>
		{glyph ?? <Missing label={missingLabel} />}
	</Link>
);

/** The detail link for a word or sentence. */
export const TextLink: React.FC<{ to: string; children: string; variant: 'word' | 'sentence' }> = ({
	to,
	children,
	variant,
}) => (
	<Link to={to} lang="ja" className={variant === 'word' ? styles.word : styles.sentence}>
		{variant === 'word' ? <span className={styles.whole}>{children}</span> : children}
	</Link>
);

/**
 * Japanese text that stays whole up to about 14em and only breaks past that, so the longest
 * readings (37 characters) cannot push the table wider than the page.
 */
export const JapaneseText: React.FC<{ value: string | null; missingLabel: string }> = ({ value, missingLabel }) =>
	value ? (
		<span lang="ja" className={styles.whole}>
			{value}
		</span>
	) : (
		<Missing label={missingLabel} />
	);

/** Readings joined with the Japanese comma, or a dash. */
export const JapaneseList: React.FC<{ values: string[]; missingLabel: string }> = ({ values, missingLabel }) => (
	<JapaneseText value={values.length > 0 ? values.join('、') : null} missingLabel={missingLabel} />
);

export const PlainText: React.FC<{ value: string | null; missingLabel: string }> = ({ value, missingLabel }) =>
	value ? <>{value}</> : <Missing label={missingLabel} />;

export const NumberValue: React.FC<{ value: number | null; missingLabel: string }> = ({ value, missingLabel }) =>
	value === null ? <Missing label={missingLabel} /> : <>{value}</>;

export const JlptLevelCell: React.FC<{ value: string | null | undefined }> = ({ value }) => {
	const level = toJlptLevel(value);

	return level ? <LevelBadge level={level} size="sm" /> : <Missing label="No JLPT level" />;
};

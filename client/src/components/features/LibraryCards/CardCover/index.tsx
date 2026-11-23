import * as React from 'react';
import classNames from 'classnames';
import styles from './CardCover.module.css';

export interface CardCoverProps {
	/** One character, from `articleCoverGlyph` or `catalogueCoverGlyph`. Decorative. */
	glyph: string;
	/** Top-left: a `LevelBadge` (articles) or a `CoverChip` with the type (catalogues). */
	badge?: React.ReactNode;
	/** A `StatusPill`: top-right from 768px, across the bottom below. */
	status?: React.ReactNode;
	/** Bottom line, e.g. "128 items". */
	caption?: React.ReactNode;
	className?: string;
}

/**
 * The informative cover of a Library Card: a large glyph on a neutral tile, with optional slots.
 * Below 768px it is the card's 96px left column; from 768px it sits on top at 2:1.
 *
 * Only the glyph is hidden from assistive technology. The slots stay readable, because a
 * processing status, a catalogue type or an item count is information the title does not carry.
 */
export const CardCover: React.FC<CardCoverProps> = ({ glyph, badge, status, caption, className }) => (
	<div className={classNames(styles.cover, className)} data-testid="card-cover">
		<span className={styles.glyph} lang="ja" aria-hidden="true">
			{glyph}
		</span>
		{badge && <span className={styles.badge}>{badge}</span>}
		{status && <span className={styles.status}>{status}</span>}
		{caption && <span className={styles.caption}>{caption}</span>}
	</div>
);

/** A neutral label for the badge slot. Shu stays reserved for JLPT levels. */
export const CoverChip: React.FC<{ children: React.ReactNode }> = ({ children }) => (
	<span className={styles.chip}>{children}</span>
);

export default CardCover;

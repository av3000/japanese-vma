import * as React from 'react';
import classNames from 'classnames';
import styles from './LevelBadge.module.css';

export const JLPT_LEVELS = ['N1', 'N2', 'N3', 'N4', 'N5'] as const;
export type JlptLevel = (typeof JLPT_LEVELS)[number];
/** `uncommon` marks material outside the JLPT lists. */
export type LevelBadgeLevel = JlptLevel | 'uncommon';

export interface LevelBadgeProps extends Omit<React.HTMLAttributes<HTMLSpanElement>, 'children'> {
	/** Accepts `N3`, `n3` or the bare number `3` as the API returns it. */
	level: LevelBadgeLevel | string | number;
	/** Optional count shown next to the level, e.g. how many kanji of that level an article has. */
	count?: number;
	size?: 'sm' | 'md';
}

/** Normalises API values (`3`, `'3'`, `'n3'`, `'N3'`, `'uncommon'`) to a display label. */
export const formatJlptLevel = (level: string | number): string => {
	const raw = String(level).trim();
	if (/^uncommon$/i.test(raw)) return 'Uncommon';
	const match = raw.match(/^n?([1-5])$/i);
	return match ? `N${match[1]}` : raw.toUpperCase();
};

/**
 * JLPT level marker in the shu accent. This is the one place the accent colour is used for
 * text, so every level in the product looks the same.
 */
export const LevelBadge: React.FC<LevelBadgeProps> = ({ level, count, size = 'md', className, ...rest }) => {
	const label = formatJlptLevel(level);
	const isNeutral = label === 'Uncommon';

	return (
		<span
			className={classNames(styles.badge, isNeutral && styles.neutral, size === 'sm' && styles.sm, className)}
			aria-label={count === undefined ? undefined : `${label}: ${count}`}
			{...rest}
		>
			{label}
			{count !== undefined && (
				<span className={styles.count} aria-hidden="true">
					{count}
				</span>
			)}
		</span>
	);
};

import * as React from 'react';
import classNames from 'classnames';
import type { ArticleResourceJlptLevels } from '@/api/generated/model/articleResourceJlptLevels';
import styles from './JlptBar.module.css';
import { dominantJlptLevel, jlptBarLabel, toJlptSegments, type JlptBarCounts } from './jlptSegments';

export { dominantJlptLevel, jlptBarLabel, toJlptSegments } from './jlptSegments';
export type { JlptBarCounts, JlptSegment, JlptSegmentLevel } from './jlptSegments';

export interface JlptBarProps {
	/** Count per JLPT level, as the article and catalogue resources return it. */
	levels: ArticleResourceJlptLevels;
	/** What is being counted, for the accessible name. Defaults to kanji. */
	counts?: JlptBarCounts;
	/** `compact` is for list rows. */
	size?: 'compact' | 'default';
	className?: string;
}

/**
 * How hard an article or catalogue is, as one bar: a segment per JLPT level (N5 → N1) sized by its
 * kanji (or word) count, with its level (`Other` for uncommon kanji) and count printed above it.
 * The dominant level is the only segment in the shu accent. Renders nothing when every count is 0,
 * e.g. while the article is still processing.
 */
export const JlptBar: React.FC<JlptBarProps> = ({ levels, counts = 'kanji', size = 'default', className }) => {
	const segments = toJlptSegments(levels);
	if (segments.length === 0) return null;

	return (
		<div
			role="img"
			aria-label={jlptBarLabel(segments, dominantJlptLevel(levels), counts)}
			className={classNames(styles.bar, size === 'compact' && styles.compact, className)}
		>
			{segments.map((segment) => (
				<span
					key={segment.level}
					aria-hidden="true"
					className={classNames(styles.item, segment.isDominant && styles.itemDominant)}
					style={{ flexGrow: segment.count }}
				>
					<span className={styles.label}>
						<span className={styles.level}>{segment.level === 'uncommon' ? 'Other' : segment.label}</span>
						<span className={styles.count}>{segment.count}</span>
					</span>
					<span
						data-level={segment.level}
						className={classNames(
							styles.segment,
							segment.isDominant && styles.dominant,
							segment.level === 'uncommon' && styles.uncommon,
						)}
					/>
				</span>
			))}
		</div>
	);
};

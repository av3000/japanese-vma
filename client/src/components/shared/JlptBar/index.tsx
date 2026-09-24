import * as React from 'react';
import classNames from 'classnames';
import type { ArticleResourceJlptLevels } from '@/api/generated/model/articleResourceJlptLevels';
import styles from './JlptBar.module.css';
import { dominantJlptLevel, jlptBarLabel, toJlptSegments } from './jlptSegments';

export { dominantJlptLevel, jlptBarLabel, toJlptSegments } from './jlptSegments';
export type { JlptSegment, JlptSegmentLevel } from './jlptSegments';

export interface JlptBarProps {
	/** Kanji count per JLPT level, as the article resource returns it. */
	levels: ArticleResourceJlptLevels;
	/** `compact` is for list rows. */
	size?: 'compact' | 'default';
	className?: string;
}

/**
 * How hard an article is, as one bar: a segment per JLPT level (N5 → N1) sized by its kanji
 * count, with the count printed after it. The dominant level is the only segment in the shu
 * accent. Renders nothing when every count is 0, e.g. while the article is still processing.
 */
export const JlptBar: React.FC<JlptBarProps> = ({ levels, size = 'default', className }) => {
	const segments = toJlptSegments(levels);
	if (segments.length === 0) return null;

	return (
		<div
			role="img"
			aria-label={jlptBarLabel(segments, dominantJlptLevel(levels))}
			className={classNames(styles.bar, size === 'compact' && styles.compact, className)}
		>
			{segments.map((segment) => (
				<React.Fragment key={segment.level}>
					<span
						aria-hidden="true"
						data-level={segment.level}
						className={classNames(
							styles.segment,
							segment.isDominant && styles.dominant,
							segment.level === 'uncommon' && styles.uncommon,
						)}
						style={{ flexGrow: segment.count }}
					/>
					<span
						aria-hidden="true"
						className={classNames(styles.count, segment.isDominant && styles.countDominant)}
					>
						{segment.count}
					</span>
				</React.Fragment>
			))}
		</div>
	);
};

import type { ArticleResourceJlptLevels } from '@/api/generated/model/articleResourceJlptLevels';
import { formatJlptLevel, JLPT_LEVELS, type JlptLevel } from '@/components/shared/LevelBadge';

export type JlptSegmentLevel = JlptLevel | 'uncommon';

export interface JlptSegment {
	level: JlptSegmentLevel;
	/** Display label from `formatJlptLevel`: `N5` … `N1`, `Uncommon`. */
	label: string;
	count: number;
	/** The level with the most kanji. Never true for `uncommon`. */
	isDominant: boolean;
}

const levelKey = (level: JlptSegmentLevel) => level.toLowerCase() as keyof ArticleResourceJlptLevels;

/**
 * The JLPT level with the most kanji, or `null` when no JLPT level has any.
 * `JLPT_LEVELS` runs N1 → N5 and only a strictly larger count replaces the current pick,
 * so a tie goes to the harder level. `uncommon` is not a level and never wins.
 */
export const dominantJlptLevel = (levels: ArticleResourceJlptLevels): JlptLevel | null => {
	let dominant: JlptLevel | null = null;
	let highest = 0;

	for (const level of JLPT_LEVELS) {
		const count = levels[levelKey(level)];
		if (count > highest) {
			dominant = level;
			highest = count;
		}
	}

	return dominant;
};

/** Segments in display order (N5 → N1, then `uncommon`), with zero-count levels dropped. */
export const toJlptSegments = (levels: ArticleResourceJlptLevels): JlptSegment[] => {
	const dominant = dominantJlptLevel(levels);
	const order: JlptSegmentLevel[] = [...JLPT_LEVELS].reverse();
	order.push('uncommon');

	return order
		.map((level) => ({
			level,
			label: formatJlptLevel(level),
			count: levels[levelKey(level)],
			isDominant: level === dominant,
		}))
		.filter((segment) => segment.count > 0);
};

/** e.g. `Mostly N3: N5 12, N4 8, N3 15, uncommon 2`. */
export const jlptBarLabel = (segments: JlptSegment[], dominant: JlptLevel | null): string => {
	const parts = segments
		.map((segment) => `${segment.level === 'uncommon' ? 'uncommon' : segment.label} ${segment.count}`)
		.join(', ');

	return dominant ? `Mostly ${dominant}: ${parts}` : `Kanji by JLPT level: ${parts}`;
};

import type { ArticleResourceJlptLevels } from '@/api/generated/model/articleResourceJlptLevels';
import type { JlptBarCounts } from '@/components/shared/JlptBar';
import { dominantJlptLevel } from '@/components/shared/JlptBar';
import type { JlptLevel } from '@/components/shared/LevelBadge';
import { resolveCatalogueFamily, type CatalogueFamily } from '@/shared/constants/catalogues';

/**
 * The Library Cards cover rule (#381). No entity has an image yet (#238 may add real covers), so
 * a cover is one large glyph on a neutral tile, chosen by a rule a reader can learn rather than
 * at random:
 *
 * - An article shows the first kanji of its Japanese title, with its dominant JLPT level as a
 *   badge. A title without kanji (all kana or Latin) falls back to 記, "record".
 * - A catalogue shows a fixed glyph for what it holds: 部 radicals, 字 kanji, 語 words,
 *   文 sentences, 記 articles. "Known" catalogues use their family's glyph.
 */
export const FALLBACK_COVER_GLYPH = '記';

export const CATALOGUE_COVER_GLYPHS: Readonly<Record<CatalogueFamily, string>> = {
	radicals: '部',
	kanji: '字',
	words: '語',
	sentences: '文',
	articles: '記',
};

const HAN = /\p{Script=Han}/u;

/** 々 repeats the previous kanji, so it is Han but means nothing on its own. */
const ITERATION_MARK = '々';

const isCoverKanji = (character: string) => character !== ITERATION_MARK && HAN.test(character);

/** Iterates by code point, so a kanji outside the Basic Multilingual Plane is kept whole. */
export const articleCoverGlyph = (titleJp: string): string =>
	Array.from(titleJp).find(isCoverKanji) ?? FALLBACK_COVER_GLYPH;

/** The level badge on an article cover, or `null` when no JLPT level has any kanji. */
export const articleCoverLevel = (levels: ArticleResourceJlptLevels): JlptLevel | null => dominantJlptLevel(levels);

export const catalogueCoverGlyph = (type: number): string => {
	const family = resolveCatalogueFamily(type);

	return family ? CATALOGUE_COVER_GLYPHS[family] : FALLBACK_COVER_GLYPH;
};

/** Words and Sentences catalogues count words; Kanji and Articles catalogues count kanji. */
export const catalogueJlptCounts = (type: number): JlptBarCounts => {
	const family = resolveCatalogueFamily(type);

	return family === 'words' || family === 'sentences' ? 'words' : 'kanji';
};

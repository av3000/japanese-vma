import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { useArticleWordPages } from './attachments';

type JlptLevels = ArticleDetailResource['jlpt_levels'];

/**
 * Processing counts each attached kanji exactly once into `jlpt_levels`, with unlevelled kanji
 * as `uncommon`, so the sum is the article's kanji count (#457).
 */
export const kanjiCountOf = (levels: JlptLevels | null | undefined): number =>
	levels ? levels.n1 + levels.n2 + levels.n3 + levels.n4 + levels.n5 + levels.uncommon : 0;

export const isProcessingRunning = (article: Pick<ArticleDetailResource, 'processing_status'>): boolean => {
	const status = article.processing_status?.status;

	return status === ProcessingStatus.pending || status === ProcessingStatus.processing;
};

export interface ArticleReadingStats {
	/** `null` while processing is pending or running. */
	kanji: number | null;
	/** `null` while processing runs or the count is loading; `undefined` when it could not be read. */
	words: number | null | undefined;
}

/**
 * The rail's kanji and word counts. Kanji come from the detail payload; words from the total of
 * the article's first word page, the same query and key the preview and the modal use, so React
 * Query sends one request for all three.
 */
export const useArticleReadingStats = (
	article: Pick<ArticleDetailResource, 'uid' | 'jlpt_levels' | 'processing_status'>,
): ArticleReadingStats => {
	const running = isProcessingRunning(article);
	const words = useArticleWordPages(article.uid);

	let wordCount: number | null | undefined = null;

	if (!running && words.isError) wordCount = undefined;
	else if (!running && !words.isPending) wordCount = words.total;

	return {
		kanji: running ? null : kanjiCountOf(article.jlpt_levels),
		words: wordCount,
	};
};

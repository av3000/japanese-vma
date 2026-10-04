import React, { useCallback, useId } from 'react';
import { useQueryClient, type InfiniteData } from '@tanstack/react-query';
import { articleKanjiFilters, articleWordFilters } from '@/api/articles/attachments';
import type { KanjiListResource } from '@/api/generated/model/kanjiListResource';
import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import type { WordListResource } from '@/api/generated/model/wordListResource';
import {
	applyKanjiViewerCatalogueState,
	getInfiniteKanjisQueryKey,
	useInfiniteKanjis,
} from '@/api/kanjis/hooks/useInfiniteKanjis';
import {
	applyWordViewerCatalogueState,
	getInfiniteWordsQueryKey,
	useInfiniteWords,
} from '@/api/words/hooks/useInfiniteWords';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { Button } from '@/components/shared/Button';
import styles from './ArticleAttachments.module.css';

/**
 * The kanji and vocabulary processing found in an article, as the same tables the dictionary
 * lists use.
 *
 * Both lists are the ordinary kanji and word indexes filtered by `article_uuid` (#268), not an
 * article-scoped endpoint: the indexes already page, filter and carry viewer catalogue state, so
 * a second route would only have duplicated them. The rail's word count reads the same query.
 */

interface ArticleAttachmentsProps {
	articleUuid: string;
	/** Signed-in viewers get a Save column on every row. */
	showSave: boolean;
	/** While processing is queued or running, an empty list means "not yet", not "none". */
	isProcessing?: boolean;
}

interface AttachmentSectionProps {
	title: string;
	noun: string;
	total: number;
	hasItems: boolean;
	isError: boolean;
	hasNextPage: boolean;
	isFetchingNextPage: boolean;
	onLoadMore: () => void;
	children: React.ReactNode;
}

const numberFormat = new Intl.NumberFormat('en-US');

const AttachmentSection: React.FC<AttachmentSectionProps> = ({
	title,
	noun,
	total,
	hasItems,
	isError,
	hasNextPage,
	isFetchingNextPage,
	onLoadMore,
	children,
}) => {
	const headingId = useId();

	return (
		<section className={styles.section} aria-labelledby={headingId}>
			<h3 className={styles.heading} id={headingId}>
				{title}
				{hasItems && <span className={styles.count}>{numberFormat.format(total)}</span>}
			</h3>

			{isError && !hasItems ? (
				<p className={styles.message} role="status">
					{title} could not be loaded. Reload the page to try again.
				</p>
			) : (
				children
			)}

			{hasNextPage && (
				<Button className={styles.more} variant="outline" onClick={onLoadMore} disabled={isFetchingNextPage}>
					{isFetchingNextPage ? 'Loading…' : `Show more ${noun}`}
				</Button>
			)}
		</section>
	);
};

export const ArticleAttachments: React.FC<ArticleAttachmentsProps> = ({
	articleUuid,
	showSave,
	isProcessing = false,
}) => {
	const queryClient = useQueryClient();
	const headingId = useId();
	const kanjiFilters = articleKanjiFilters(articleUuid);
	const wordFilters = articleWordFilters(articleUuid);
	const kanjis = useInfiniteKanjis({ filters: kanjiFilters, enabled: Boolean(articleUuid) });
	const words = useInfiniteWords({ filters: wordFilters, enabled: Boolean(articleUuid) });

	// Saving from a row writes the new state into the list it came from, as the dictionary lists do.
	const handleKanjiSaved = useCallback(
		(kanjiId: number, state: ViewerCatalogueStateResource) => {
			queryClient.setQueryData<InfiniteData<KanjiListResource>>(
				getInfiniteKanjisQueryKey(articleKanjiFilters(articleUuid)),
				(data) => applyKanjiViewerCatalogueState(data, kanjiId, state),
			);
		},
		[queryClient, articleUuid],
	);

	const handleWordSaved = useCallback(
		(wordId: number, state: ViewerCatalogueStateResource) => {
			queryClient.setQueryData<InfiniteData<WordListResource>>(
				getInfiniteWordsQueryKey(articleWordFilters(articleUuid)),
				(data) => applyWordViewerCatalogueState(data, wordId, state),
			);
		},
		[queryClient, articleUuid],
	);

	const pendingHint = isProcessing ? 'They appear here once processing finishes.' : undefined;

	return (
		<section className={styles.attachments} aria-labelledby={headingId}>
			<h2 className={styles.title} id={headingId}>
				Kanji and words in this reading
			</h2>

			<AttachmentSection
				title="Kanji"
				noun="kanji"
				total={kanjis.total}
				hasItems={kanjis.kanjis.length > 0}
				isError={kanjis.isError}
				hasNextPage={Boolean(kanjis.hasNextPage)}
				isFetchingNextPage={kanjis.isFetchingNextPage}
				onLoadMore={() => void kanjis.fetchNextPage()}
			>
				<KanjiTable
					kanjis={kanjis.kanjis}
					loading={kanjis.isPending}
					showSave={showSave}
					empty={{ title: 'No kanji have been attached to this article yet.', hint: pendingHint }}
					onBookmarkStateChange={handleKanjiSaved}
				/>
			</AttachmentSection>

			<AttachmentSection
				title="Words"
				noun="words"
				total={words.total}
				hasItems={words.words.length > 0}
				isError={words.isError}
				hasNextPage={Boolean(words.hasNextPage)}
				isFetchingNextPage={words.isFetchingNextPage}
				onLoadMore={() => void words.fetchNextPage()}
			>
				<WordTable
					words={words.words}
					loading={words.isPending}
					showSave={showSave}
					empty={{ title: 'No words have been attached to this article yet.', hint: pendingHint }}
					onBookmarkStateChange={handleWordSaved}
				/>
			</AttachmentSection>
		</section>
	);
};

export default ArticleAttachments;

import React, { useCallback, useId, useMemo, useRef } from 'react';
import { Link } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import {
	articleKanjiFilters,
	articleWordFilters,
	useArticleKanjiPages,
	useArticleWordPages,
} from '@/api/articles/attachments';
import { getKanjiIndexQueryKey } from '@/api/generated/kanji/kanji';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import type { WordResource } from '@/api/generated/model/wordResource';
import { getWordIndexQueryKey } from '@/api/generated/word/word';
import { updateCachedRows } from '@/api/pagedIndex';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import {
	PagedControls,
	PagedListFilters,
	presentText,
	presentValues,
	RelatedListModal,
	RelatedPreview,
} from '@/components/features/japanese/dictionaryList';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { useModal } from '@/hooks/useModal';
import styles from './ArticleAttachments.module.css';

/**
 * The kanji and words processing found in an article: a short preview under the text, and the full
 * lists in modals with numbered pages, a page size and a keyword search (#522).
 *
 * Both lists are the ordinary kanji and word indexes filtered by `article_uuid` (#268), not an
 * article-scoped endpoint. The preview, the modal's first page and the rail's word count read the
 * same first-page query.
 */

/** Five of each keep the section short under the text; the rest are one click away in the modal. */
export const PREVIEW_KANJI = 5;
export const PREVIEW_WORDS = 5;

interface ArticleAttachmentsProps {
	articleUuid: string;
	/** Signed-in viewers get a Save column in the full lists. */
	showSave: boolean;
	/** While processing is queued or running, an empty list means "not yet", not "none". */
	isProcessing?: boolean;
}

/** Saving from a row writes the new state into every cached list for the article. */
const useSaveStateWriter = (queryKey: readonly unknown[]) => {
	const queryClient = useQueryClient();

	return useCallback(
		(rowId: number, state: ViewerCatalogueStateResource) =>
			queryClient.setQueriesData({ queryKey }, (data) =>
				updateCachedRows<{ id: number; viewer_catalogue_state?: ViewerCatalogueStateResource | null }>(
					data,
					rowId,
					(row) => ({ ...row, viewer_catalogue_state: state }),
				),
			),
		[queryClient, queryKey],
	);
};

const KanjiListModal: React.FC<{ articleUuid: string; showSave: boolean; emptyHint?: string }> = ({
	articleUuid,
	showSave,
	emptyHint,
}) => {
	const list = useArticleKanjiPages(articleUuid);
	const listKey = useMemo(() => getKanjiIndexQueryKey(articleKanjiFilters(articleUuid)), [articleUuid]);
	const writeSaveState = useSaveStateWriter(listKey);

	return (
		<>
			<PagedListFilters list={list} noun="kanji" placeholder="Kanji, reading or meaning" />
			<KanjiTable
				kanjis={list.rows}
				loading={list.isPending}
				showSave={showSave}
				empty={
					list.keyword
						? { title: `No kanji match “${list.keyword}”.`, hint: 'Try a reading or an English meaning.' }
						: { title: 'No kanji have been attached to this article yet.', hint: emptyHint }
				}
				onBookmarkStateChange={writeSaveState}
			/>
			<PagedControls list={list} noun="kanji" label="Kanji pages" />
		</>
	);
};

const WordListModal: React.FC<{ articleUuid: string; showSave: boolean; emptyHint?: string }> = ({
	articleUuid,
	showSave,
	emptyHint,
}) => {
	const list = useArticleWordPages(articleUuid);
	const listKey = useMemo(() => getWordIndexQueryKey(articleWordFilters(articleUuid)), [articleUuid]);
	const writeSaveState = useSaveStateWriter(listKey);

	return (
		<>
			<PagedListFilters list={list} noun="words" placeholder="Word, reading or meaning" />
			<WordTable
				words={list.rows}
				loading={list.isPending}
				showSave={showSave}
				empty={
					list.keyword
						? { title: `No words match “${list.keyword}”.`, hint: 'Try a reading or an English meaning.' }
						: { title: 'No words have been attached to this article yet.', hint: emptyHint }
				}
				onBookmarkStateChange={writeSaveState}
			/>
			<PagedControls list={list} noun="words" label="Word pages" />
		</>
	);
};

export const ArticleAttachments: React.FC<ArticleAttachmentsProps> = ({
	articleUuid,
	showSave,
	isProcessing = false,
}) => {
	const headingId = useId();
	const kanjiDialogRef = useRef<HTMLDialogElement | null>(null);
	const wordDialogRef = useRef<HTMLDialogElement | null>(null);
	const kanjiModal = useModal(kanjiDialogRef, { id: 'article-kanji-modal' });
	const wordModal = useModal(wordDialogRef, { id: 'article-words-modal' });
	const kanji = useArticleKanjiPages(articleUuid);
	const words = useArticleWordPages(articleUuid);
	const emptyHint = isProcessing ? 'They appear here once processing finishes.' : undefined;
	const emptySuffix = emptyHint ? ` ${emptyHint}` : '';

	return (
		<section className={styles.attachments} aria-labelledby={headingId}>
			<h2 className={styles.title} id={headingId}>
				Kanji and words in this reading
			</h2>

			<RelatedPreview
				headingLevel={3}
				title="Kanji"
				noun="kanji"
				total={kanji.total}
				isPending={kanji.isPending}
				isError={kanji.isError}
				isEmpty={kanji.rows.length === 0}
				emptyMessage={`No kanji have been attached to this article yet.${emptySuffix}`}
				controller={kanjiModal}
			>
				<ul className={styles.glyphs}>
					{kanji.rows.slice(0, PREVIEW_KANJI).map((item: KanjiResource) => (
						<li key={item.uuid}>
							<Link
								to={`/kanji/${item.uuid}`}
								className={styles.glyph}
								lang="ja"
								title={presentValues(item.meanings).join(', ') || undefined}
							>
								{item.character}
							</Link>
						</li>
					))}
				</ul>
			</RelatedPreview>

			<RelatedPreview
				headingLevel={3}
				title="Words"
				noun="words"
				total={words.total}
				isPending={words.isPending}
				isError={words.isError}
				isEmpty={words.rows.length === 0}
				emptyMessage={`No words have been attached to this article yet.${emptySuffix}`}
				controller={wordModal}
			>
				<ul className={styles.words}>
					{words.rows.slice(0, PREVIEW_WORDS).map((item: WordResource) => {
						const reading = presentText(item.furigana);

						return (
							<li key={item.uuid} className={styles.word}>
								<Link to={`/word/${item.uuid}`} lang="ja" className={styles.wordLink}>
									{item.word}
								</Link>
								{reading && reading !== item.word ? (
									<span className={styles.reading} lang="ja">
										{reading}
									</span>
								) : null}
							</li>
						);
					})}
				</ul>
			</RelatedPreview>

			<RelatedListModal controller={kanjiModal} title="Kanji in this reading">
				<KanjiListModal articleUuid={articleUuid} showSave={showSave} emptyHint={emptyHint} />
			</RelatedListModal>
			<RelatedListModal controller={wordModal} title="Words in this reading">
				<WordListModal articleUuid={articleUuid} showSave={showSave} emptyHint={emptyHint} />
			</RelatedListModal>
		</section>
	);
};

export default ArticleAttachments;

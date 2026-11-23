import * as React from 'react';
import { useMemo, useRef } from 'react';
import { kanjiArticleFilters, useKanjiArticlePages } from '@/api/articles/related';
import { getWordIndexQueryKey } from '@/api/generated/word/word';
import { useKanjiSentencePages, useKanjiWordPages } from '@/api/kanjis/related';
import { useViewerStateWriter } from '@/api/pagedIndex';
import { WORD_VIEWER_CATALOGUE_INCLUDE } from '@/api/words/hooks/useInfiniteWords';
import {
	PagedControls,
	PagedListFilters,
	RelatedListModal,
	RelatedPreview,
} from '@/components/features/japanese/dictionaryList';
import { SentenceTable } from '@/components/features/japanese/sentence/SentenceTable';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { useModal } from '@/hooks/useModal';
import styles from './Related.module.css';
import { RelatedArticleList } from './RelatedArticleList';
import { ArticleListModalBody, RELATED_PREVIEW_ROWS } from './RelatedArticles';

interface KanjiRelatedProps {
	kanji: { id: number; uuid: string; character: string };
	/** Signed-in viewers get a Save column on word rows. */
	showSave: boolean;
}

/** Save from any row reaches every cached page, size and search of the kanji's words. */
const useWordSaveStateWriter = (kanjiUuid: string) =>
	useViewerStateWriter(
		useMemo(
			() => getWordIndexQueryKey({ kanji_uuid: kanjiUuid, include: WORD_VIEWER_CATALOGUE_INCLUDE }),
			[kanjiUuid],
		),
	);

/*
 * The modal bodies read their own paged list, so paging and searching in the modal never change
 * the preview. Unfiltered page 1 is the same query in both, so opening the modal fetches nothing.
 */

const WordListModalBody: React.FC<{ kanjiUuid: string; showSave: boolean }> = ({ kanjiUuid, showSave }) => {
	const list = useKanjiWordPages(kanjiUuid);
	const writeSaveState = useWordSaveStateWriter(kanjiUuid);

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
						: { title: 'No words use this kanji yet.' }
				}
				onBookmarkStateChange={writeSaveState}
			/>
			<PagedControls list={list} noun="words" label="Word pages" />
		</>
	);
};

const SentenceListModalBody: React.FC<{ kanjiUuid: string }> = ({ kanjiUuid }) => {
	const list = useKanjiSentencePages(kanjiUuid);

	return (
		<>
			<PagedListFilters list={list} noun="sentences" placeholder="Words in the sentence" />
			<SentenceTable
				sentences={list.rows}
				loading={list.isPending}
				empty={
					list.keyword
						? { title: `No sentences match “${list.keyword}”.`, hint: 'Try other words.' }
						: { title: 'No sentences use this kanji yet.' }
				}
			/>
			<PagedControls list={list} noun="sentences" label="Sentence pages" />
		</>
	);
};

/**
 * The words, sentences and articles written with one kanji: five of each under the entry, and the
 * full lists in modals with numbered pages, a page size and a search (#352). Each list is an index
 * filtered by this kanji.
 */
export const KanjiRelated: React.FC<KanjiRelatedProps> = ({ kanji, showSave }) => {
	const wordDialogRef = useRef<HTMLDialogElement | null>(null);
	const sentenceDialogRef = useRef<HTMLDialogElement | null>(null);
	const articleDialogRef = useRef<HTMLDialogElement | null>(null);
	const wordModal = useModal(wordDialogRef, { id: 'kanji-words-modal' });
	const sentenceModal = useModal(sentenceDialogRef, { id: 'kanji-sentences-modal' });
	const articleModal = useModal(articleDialogRef, { id: 'kanji-articles-modal' });

	const words = useKanjiWordPages(kanji.uuid);
	const sentences = useKanjiSentencePages(kanji.uuid);
	const articles = useKanjiArticlePages(kanji.id);
	const writeWordSaveState = useWordSaveStateWriter(kanji.uuid);

	const wordsTitle = `Words with ${kanji.character}`;
	const sentencesTitle = `Sentences with ${kanji.character}`;
	const articlesTitle = `Articles with ${kanji.character}`;

	return (
		<div className={styles.sections}>
			<RelatedPreview
				title={wordsTitle}
				noun="words"
				total={words.total}
				isPending={words.isPending}
				isError={words.isError}
				isEmpty={words.rows.length === 0}
				emptyMessage="No words use this kanji yet."
				controller={wordModal}
			>
				<WordTable
					words={words.rows.slice(0, RELATED_PREVIEW_ROWS)}
					showSave={showSave}
					empty={{ title: 'No words use this kanji yet.' }}
					onBookmarkStateChange={writeWordSaveState}
				/>
			</RelatedPreview>

			<RelatedPreview
				title={sentencesTitle}
				noun="sentences"
				total={sentences.total}
				isPending={sentences.isPending}
				isError={sentences.isError}
				isEmpty={sentences.rows.length === 0}
				emptyMessage="No sentences use this kanji yet."
				controller={sentenceModal}
			>
				<SentenceTable
					sentences={sentences.rows.slice(0, RELATED_PREVIEW_ROWS)}
					empty={{ title: 'No sentences use this kanji yet.' }}
				/>
			</RelatedPreview>

			<RelatedPreview
				title={articlesTitle}
				noun="articles"
				total={articles.total}
				isPending={articles.isPending}
				isError={articles.isError}
				isEmpty={articles.rows.length === 0}
				emptyMessage="No articles use this kanji yet."
				controller={articleModal}
			>
				<RelatedArticleList articles={articles.rows.slice(0, RELATED_PREVIEW_ROWS)} />
			</RelatedPreview>

			<RelatedListModal controller={wordModal} title={wordsTitle}>
				<WordListModalBody kanjiUuid={kanji.uuid} showSave={showSave} />
			</RelatedListModal>
			<RelatedListModal controller={sentenceModal} title={sentencesTitle}>
				<SentenceListModalBody kanjiUuid={kanji.uuid} />
			</RelatedListModal>
			<RelatedListModal controller={articleModal} title={articlesTitle}>
				<ArticleListModalBody
					filters={kanjiArticleFilters(kanji.id)}
					emptyMessage="No articles use this kanji yet."
				/>
			</RelatedListModal>
		</div>
	);
};

export default KanjiRelated;

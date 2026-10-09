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
} from '@/components/features/japanese/dictionaryList';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { Button } from '@/components/shared/Button';
import { DialogModal } from '@/components/shared/DialogModal';
import { useModal, type ModalController } from '@/hooks/useModal';
import styles from './ArticleAttachments.module.css';

/**
 * The kanji and words processing found in an article: a short preview under the text, and the full
 * lists in modals with numbered pages, a page size and a keyword search (#522).
 *
 * Both lists are the ordinary kanji and word indexes filtered by `article_uuid` (#268), not an
 * article-scoped endpoint. The preview, the modal's first page and the rail's word count read the
 * same first-page query.
 */

export const PREVIEW_KANJI = 12;
export const PREVIEW_WORDS = 8;

const numberFormat = new Intl.NumberFormat('en-US');

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

interface ListModalProps {
	controller: ModalController;
	title: string;
	children: React.ReactNode;
}

const ListModal: React.FC<ListModalProps> = ({ controller, title, children }) => {
	const titleId = useId();

	if (!controller.isRendered) return null;

	return (
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			size="xl"
			ariaLabel={title}
		>
			<DialogModal.Header>
				<DialogModal.Title id={titleId}>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>
				<div className={styles.modalBody}>{children}</div>
			</DialogModal.Body>
		</DialogModal>
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

interface PreviewBlockProps {
	title: string;
	noun: string;
	total: number;
	isPending: boolean;
	isError: boolean;
	isEmpty: boolean;
	emptyMessage: string;
	controller: ModalController;
	children: React.ReactNode;
}

const PreviewBlock: React.FC<PreviewBlockProps> = ({
	title,
	noun,
	total,
	isPending,
	isError,
	isEmpty,
	emptyMessage,
	controller,
	children,
}) => {
	const headingId = useId();

	return (
		<section className={styles.section} aria-labelledby={headingId}>
			<h3 className={styles.heading} id={headingId}>
				{title}
				{!isPending && !isError && total > 0 ? (
					<span className={styles.count}>{numberFormat.format(total)}</span>
				) : null}
			</h3>
			{isPending ? <p className={styles.message}>Loading {noun}…</p> : null}
			{isError ? (
				<p className={styles.message} role="status">
					{title} could not be loaded. Reload the page to try again.
				</p>
			) : null}
			{!isPending && !isError && isEmpty ? <p className={styles.message}>{emptyMessage}</p> : null}
			{!isPending && !isError && !isEmpty ? (
				<>
					{children}
					<Button
						className={styles.seeAll}
						variant="outline"
						aria-controls={controller.id}
						aria-expanded={controller.isOpen}
						onClick={controller.open}
					>
						{`See all ${numberFormat.format(total)} ${noun}`}
					</Button>
				</>
			) : null}
		</section>
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

			<PreviewBlock
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
			</PreviewBlock>

			<PreviewBlock
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
			</PreviewBlock>

			<ListModal controller={kanjiModal} title="Kanji in this reading">
				<KanjiListModal articleUuid={articleUuid} showSave={showSave} emptyHint={emptyHint} />
			</ListModal>
			<ListModal controller={wordModal} title="Words in this reading">
				<WordListModal articleUuid={articleUuid} showSave={showSave} emptyHint={emptyHint} />
			</ListModal>
		</section>
	);
};

export default ArticleAttachments;

import React, { useCallback, useMemo, useRef, useState } from 'react';
import { useQueryClient, type InfiniteData } from '@tanstack/react-query';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import {
	catalogueKanjiFilters,
	catalogueRadicalFilters,
	catalogueSentenceFilters,
	catalogueWordFilters,
	useRemoveCatalogueItem,
} from '@/api/catalogues/items';
import type { KanjiListResource } from '@/api/generated/model/kanjiListResource';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import type { RadicalResource } from '@/api/generated/model/radicalResource';
import type { SentenceResource } from '@/api/generated/model/sentenceResource';
import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import type { WordListResource } from '@/api/generated/model/wordListResource';
import type { WordResource } from '@/api/generated/model/wordResource';
import {
	applyKanjiViewerCatalogueState,
	getInfiniteKanjisQueryKey,
	useInfiniteKanjis,
} from '@/api/kanjis/hooks/useInfiniteKanjis';
import { useInfiniteRadicals } from '@/api/radicals/hooks/useInfiniteRadicals';
import { useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import {
	applyWordViewerCatalogueState,
	getInfiniteWordsQueryKey,
	useInfiniteWords,
} from '@/api/words/hooks/useInfiniteWords';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import { RadicalTable } from '@/components/features/japanese/radical/RadicalTable';
import { SentenceTable } from '@/components/features/japanese/sentence/SentenceTable';
import { WordTable } from '@/components/features/japanese/word/WordTable';
import { Button } from '@/components/shared/Button';
import type { DataTableColumn, DataTableEmpty } from '@/components/shared/DataTable';
import { ConfirmModal } from '@/components/shared/modals';
import { useModal } from '@/hooks/useModal';
import { resolveCatalogueFamily, type CatalogueFamily } from '@/shared/constants/catalogues';
import { CatalogueArticleList } from './CatalogueArticleList';
import styles from './CatalogueItems.module.css';

interface CatalogueItemsProps {
	catalogueUuid: string;
	/** The catalogue's numeric type; mapped to its family once, here. */
	catalogueType: number;
	/** The detail payload's `items`, read only for article catalogues. */
	payloadItems: unknown[];
	isOwner: boolean;
	/** Signed-in viewers get a Save column on kanji and word rows. */
	showSave: boolean;
}

interface PendingRemoval {
	id: number;
	label: string;
}

const SINGULAR_NOUNS: Record<CatalogueFamily, string> = {
	kanji: 'kanji',
	words: 'word',
	radicals: 'radical',
	sentences: 'sentence',
	articles: 'article',
};

const ADD_HINTS: Record<CatalogueFamily, string> = {
	kanji: 'Use Save to a catalogue on any kanji page or in the Kanji list to add some.',
	words: 'Use Save to a catalogue on any word page or in the Words list to add some.',
	radicals: 'Use Save to a catalogue on any radical page to add some.',
	sentences: 'Use Save to a catalogue on any sentence page to add some.',
	articles: 'Use Save to a catalogue on any article page to add some.',
};

const LOAD_ERROR = 'The items could not be loaded. Reload the page to try again.';

/** A short accessible name for a row, so each Remove button says what it removes. */
const shortLabel = (text: string | null | undefined, max = 24) => {
	const value = (text ?? '').trim();
	const characters = Array.from(value);

	return characters.length > max ? `${characters.slice(0, max).join('')}…` : value || 'this item';
};

interface ListQuery {
	total: number;
	isError: boolean;
	hasNextPage?: boolean;
	isFetchingNextPage: boolean;
	fetchNextPage: () => unknown;
}

/** "Show more" under a table, and the user-written error in place of a list that failed. */
const ItemsList: React.FC<{
	query: ListQuery;
	hasItems: boolean;
	noun: string;
	children: React.ReactNode;
}> = ({ query, hasItems, noun, children }) => (
	<>
		{query.isError && !hasItems ? (
			<p className={styles.message} role="status">
				{LOAD_ERROR}
			</p>
		) : (
			children
		)}
		{query.hasNextPage ? (
			<Button
				className={styles.more}
				variant="outline"
				onClick={() => void query.fetchNextPage()}
				disabled={query.isFetchingNextPage}
			>
				{query.isFetchingNextPage ? 'Loading…' : `Show more ${noun}`}
			</Button>
		) : null}
	</>
);

/**
 * A catalogue's items: kanji, words, radicals and sentences as the dictionary tables, 25 per page,
 * and articles as a compact list. The owner can switch on "Manage items" to get a Remove button
 * on every row; each removal is confirmed first.
 */
export const CatalogueItems: React.FC<CatalogueItemsProps> = ({
	catalogueUuid,
	catalogueType,
	payloadItems,
	isOwner,
	showSave,
}) => {
	const family = resolveCatalogueFamily(catalogueType);
	const [isManaging, setIsManaging] = useState(false);
	const [pendingRemoval, setPendingRemoval] = useState<PendingRemoval | null>(null);
	const dialogRef = useRef<HTMLDialogElement>(null);
	const confirmRemoval = useModal(dialogRef, {
		id: 'catalogue-remove-item-modal',
		onClose: () => setPendingRemoval(null),
	});
	const removeItem = useRemoveCatalogueItem(catalogueUuid, family ?? 'articles');
	const canManage = isOwner && isManaging;

	const askToRemove = useCallback(
		(id: number, label: string) => {
			setPendingRemoval({ id, label });
			confirmRemoval.open();
		},
		[confirmRemoval],
	);

	const removeColumn = useCallback(
		<Row,>(idOf: (row: Row) => number, labelOf: (row: Row) => string): DataTableColumn<Row>[] =>
			canManage
				? [
						{
							id: 'remove',
							header: 'Remove',
							headerHidden: true,
							width: 'shrink',
							cell: (row) => (
								<Button
									variant="secondary-outline"
									size="sm"
									onClick={() => askToRemove(idOf(row), labelOf(row))}
									aria-label={`Remove ${labelOf(row)} from this catalogue`}
								>
									Remove
								</Button>
							),
						},
					]
				: [],
		[canManage, askToRemove],
	);

	if (!family) {
		return (
			<p className={styles.message} role="status">
				This kind of catalogue can't be shown here.
			</p>
		);
	}

	const noun = SINGULAR_NOUNS[family];
	const empty: DataTableEmpty = {
		title: 'This catalogue has no items yet.',
		hint: isOwner ? ADD_HINTS[family] : undefined,
	};

	return (
		<section className={styles.items} aria-label="Items">
			{isOwner ? (
				<div className={styles.toolbar}>
					<Button
						variant="outline"
						size="sm"
						aria-pressed={isManaging}
						onClick={() => setIsManaging((current) => !current)}
					>
						{isManaging ? 'Done managing' : 'Manage items'}
					</Button>
				</div>
			) : null}

			{family === 'kanji' ? (
				<KanjiItems
					catalogueUuid={catalogueUuid}
					showSave={showSave}
					empty={empty}
					trailingColumns={removeColumn<KanjiResource>(
						(kanji) => kanji.id,
						(kanji) => kanji.character,
					)}
				/>
			) : null}
			{family === 'words' ? (
				<WordItems
					catalogueUuid={catalogueUuid}
					showSave={showSave}
					empty={empty}
					trailingColumns={removeColumn<WordResource>(
						(word) => word.id,
						(word) => word.word,
					)}
				/>
			) : null}
			{family === 'radicals' ? (
				<RadicalItems
					catalogueUuid={catalogueUuid}
					empty={empty}
					trailingColumns={removeColumn<RadicalResource>(
						(radical) => radical.id,
						(radical) => shortLabel(radical.radical),
					)}
				/>
			) : null}
			{family === 'sentences' ? (
				<SentenceItems
					catalogueUuid={catalogueUuid}
					empty={empty}
					trailingColumns={removeColumn<SentenceResource>(
						(sentence) => sentence.id,
						(sentence) => shortLabel(sentence.content),
					)}
				/>
			) : null}
			{family === 'articles' ? (
				<CatalogueArticleList
					articles={payloadItems as CatalogueArticleItem[]}
					empty={empty}
					onRemove={
						canManage ? (article) => askToRemove(article.id, shortLabel(article.title_jp)) : undefined
					}
				/>
			) : null}

			<ConfirmModal
				controller={confirmRemoval}
				title={`Remove this ${noun}?`}
				confirmLabel="Yes, remove"
				confirmVariant="danger"
				ariaLabel={`Remove ${noun} from catalogue`}
				isConfirmLoading={removeItem.isPending}
				onConfirm={() => {
					if (!pendingRemoval) return;
					removeItem.mutate(pendingRemoval.id, { onSettled: () => confirmRemoval.close() });
				}}
			>
				{pendingRemoval
					? `This removes ${pendingRemoval.label} from the catalogue. You can add it again later.`
					: null}
			</ConfirmModal>
		</section>
	);
};

interface FamilyItemsProps<Row> {
	catalogueUuid: string;
	empty: DataTableEmpty;
	trailingColumns: DataTableColumn<Row>[];
}

const KanjiItems: React.FC<FamilyItemsProps<KanjiResource> & { showSave: boolean }> = ({
	catalogueUuid,
	showSave,
	empty,
	trailingColumns,
}) => {
	const queryClient = useQueryClient();
	const filters = useMemo(() => catalogueKanjiFilters(catalogueUuid), [catalogueUuid]);
	const { kanjis, ...query } = useInfiniteKanjis({ filters });
	const handleSaved = useCallback(
		(kanjiId: number, state: ViewerCatalogueStateResource) =>
			queryClient.setQueryData<InfiniteData<KanjiListResource>>(getInfiniteKanjisQueryKey(filters), (data) =>
				applyKanjiViewerCatalogueState(data, kanjiId, state),
			),
		[queryClient, filters],
	);

	return (
		<ItemsList query={query} hasItems={kanjis.length > 0} noun="kanji">
			<KanjiTable
				kanjis={kanjis}
				loading={query.isPending}
				showSave={showSave}
				empty={empty}
				onBookmarkStateChange={handleSaved}
				trailingColumns={trailingColumns}
			/>
		</ItemsList>
	);
};

const WordItems: React.FC<FamilyItemsProps<WordResource> & { showSave: boolean }> = ({
	catalogueUuid,
	showSave,
	empty,
	trailingColumns,
}) => {
	const queryClient = useQueryClient();
	const filters = useMemo(() => catalogueWordFilters(catalogueUuid), [catalogueUuid]);
	const { words, ...query } = useInfiniteWords({ filters });
	const handleSaved = useCallback(
		(wordId: number, state: ViewerCatalogueStateResource) =>
			queryClient.setQueryData<InfiniteData<WordListResource>>(getInfiniteWordsQueryKey(filters), (data) =>
				applyWordViewerCatalogueState(data, wordId, state),
			),
		[queryClient, filters],
	);

	return (
		<ItemsList query={query} hasItems={words.length > 0} noun="words">
			<WordTable
				words={words}
				loading={query.isPending}
				showSave={showSave}
				empty={empty}
				onBookmarkStateChange={handleSaved}
				trailingColumns={trailingColumns}
			/>
		</ItemsList>
	);
};

const RadicalItems: React.FC<FamilyItemsProps<RadicalResource>> = ({ catalogueUuid, empty, trailingColumns }) => {
	const filters = useMemo(() => catalogueRadicalFilters(catalogueUuid), [catalogueUuid]);
	const { radicals, ...query } = useInfiniteRadicals({ filters });

	return (
		<ItemsList query={query} hasItems={radicals.length > 0} noun="radicals">
			<RadicalTable
				radicals={radicals}
				loading={query.isPending}
				empty={empty}
				trailingColumns={trailingColumns}
			/>
		</ItemsList>
	);
};

const SentenceItems: React.FC<FamilyItemsProps<SentenceResource>> = ({ catalogueUuid, empty, trailingColumns }) => {
	const filters = useMemo(() => catalogueSentenceFilters(catalogueUuid), [catalogueUuid]);
	const { sentences, ...query } = useInfiniteSentences({ filters });

	return (
		<ItemsList query={query} hasItems={sentences.length > 0} noun="sentences">
			<SentenceTable
				sentences={sentences}
				loading={query.isPending}
				empty={empty}
				trailingColumns={trailingColumns}
			/>
		</ItemsList>
	);
};

export default CatalogueItems;

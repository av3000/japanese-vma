import * as React from 'react';
import type { ViewerCatalogueStateResource, WordResource } from '@/api/generated/model';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import {
	JapaneseText,
	JlptLevelCell,
	PlainText,
	TextLink,
	presentAllValues,
	presentText,
} from '@/components/features/japanese/dictionaryList';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import { SavedListType } from '@/shared/constants/enums';
import styles from './WordTable.module.css';

export interface WordTableProps {
	words: WordResource[];
	/** Save is for signed-in viewers only; the column is left out otherwise. */
	showSave: boolean;
	loading?: boolean;
	empty: DataTableEmpty;
	onBookmarkStateChange?: (wordId: number, state: ViewerCatalogueStateResource) => void;
}

const baseColumns: DataTableColumn<WordResource>[] = [
	{
		id: 'word',
		header: 'Word',
		rowHeader: true,
		cell: (word) => (
			<TextLink to={`/word/${word.uuid}`} variant="word">
				{word.word}
			</TextLink>
		),
	},
	{
		id: 'reading',
		header: 'Reading',
		cell: (word) => <JapaneseText value={presentText(word.furigana)} missingLabel="No reading" />,
	},
	{
		id: 'meaning',
		header: 'Meaning',
		width: 'fill',
		cellClassName: styles.meaning,
		cell: (word) => <PlainText value={presentText(word.meaning)} missingLabel="No meaning" />,
	},
	{
		id: 'type',
		header: 'Type',
		priority: 'low',
		mobileLabel: 'Type',
		cellClassName: styles.type,
		// `word_types` is the clean list; the raw `word_type` string ends in a stray "|".
		cell: (word) => (
			<PlainText value={presentAllValues(word.word_types).join(', ') || null} missingLabel="No word type" />
		),
	},
	{ id: 'jlpt', header: 'JLPT', cell: (word) => <JlptLevelCell value={word.jlpt} /> },
];

const stackedAreas = ['word reading jlpt', 'meaning meaning meaning', 'type type type'];

/**
 * Word, reading and level share the first line. The first two tracks shrink below their content
 * only when the line is too narrow (the longest word and reading at 320px), and then wrap.
 */
const FIRST_LINE = 'minmax(0, max-content) minmax(0, max-content) minmax(0, 1fr)';
const STACKED = { columns: FIRST_LINE, areas: stackedAreas };
const STACKED_WITH_SAVE = {
	columns: `${FIRST_LINE} auto`,
	areas: stackedAreas.map((area) => `${area} save`),
};

/**
 * The Words list as an Index table: word, reading, meaning, type and level, plus Save for
 * signed-in viewers. The meaning column absorbs the width; Type hides from 768 to 1023px.
 */
export const WordTable: React.FC<WordTableProps> = ({ words, showSave, loading, empty, onBookmarkStateChange }) => {
	const columns: DataTableColumn<WordResource>[] = showSave
		? [
				...baseColumns,
				{
					id: 'save',
					header: 'Save',
					headerHidden: true,
					width: 'shrink',
					cellClassName: styles.actionCell,
					cell: (word) => (
						<AuthorizedBookmarkWidget
							compact
							itemLabel={word.word}
							instanceObjectType={SavedListType.WORDS}
							isKnownType={SavedListType.KNOWNWORDS}
							entityId={word.id}
							modalTitle="Choose Word List to add"
							initialIsBookmarked={word.viewer_catalogue_state?.is_saved ?? false}
							initialIsKnown={word.viewer_catalogue_state?.is_known ?? false}
							loadOnMount={false}
							onStateChange={(state) =>
								onBookmarkStateChange?.(word.id, {
									is_saved: state.isBookmarked,
									is_known: state.isKnown,
								})
							}
						/>
					),
				},
			]
		: baseColumns;

	return (
		<DataTable
			label="Words"
			columns={columns}
			rows={words}
			getRowKey={(word) => word.uuid}
			loading={loading}
			empty={empty}
			stacked={showSave ? STACKED_WITH_SAVE : STACKED}
		/>
	);
};

export default WordTable;

import * as React from 'react';
import type { ViewerCatalogueStateResource, KanjiResource } from '@/api/generated/model';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import {
	GlyphLink,
	JapaneseList,
	JlptLevelCell,
	NumberValue,
	PlainText,
	presentRank,
	presentValues,
} from '@/components/features/japanese/dictionaryList';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import { SavedListType } from '@/shared/constants/enums';
import styles from './KanjiTable.module.css';

export interface KanjiTableProps {
	kanjis: KanjiResource[];
	/** Save is for signed-in viewers only; the column is left out otherwise. */
	showSave: boolean;
	loading?: boolean;
	empty: DataTableEmpty;
	onBookmarkStateChange?: (kanjiId: number, state: ViewerCatalogueStateResource) => void;
}

const baseColumns: DataTableColumn<KanjiResource>[] = [
	{
		id: 'glyph',
		header: 'Kanji',
		rowHeader: true,
		width: 'shrink',
		cellClassName: styles.glyphCell,
		cell: (kanji) => <GlyphLink to={`/kanji/${kanji.uuid}`} glyph={kanji.character} missingLabel="Kanji" />,
	},
	{
		id: 'meaning',
		header: 'Meaning',
		width: 'fill',
		cellClassName: styles.lead,
		cell: (kanji) => (
			<PlainText value={presentValues(kanji.meanings).join(', ') || null} missingLabel="No meaning" />
		),
	},
	{
		id: 'on',
		header: 'On’yomi',
		mobileLabel: 'On',
		cell: (kanji) => <JapaneseList values={presentValues(kanji.onyomi)} missingLabel="No on’yomi" />,
	},
	{
		id: 'kun',
		header: 'Kun’yomi',
		mobileLabel: 'Kun',
		cell: (kanji) => <JapaneseList values={presentValues(kanji.kunyomi)} missingLabel="No kun’yomi" />,
	},
	{
		id: 'strokes',
		header: 'Strokes',
		numeric: true,
		mobileLabel: 'Strokes',
		cell: (kanji) => <NumberValue value={presentRank(kanji.stroke_count)} missingLabel="No stroke count" />,
	},
	{ id: 'jlpt', header: 'JLPT', mobileLabel: 'JLPT', cell: (kanji) => <JlptLevelCell value={kanji.jlpt} /> },
	{
		id: 'freq',
		header: 'Freq.',
		numeric: true,
		priority: 'low',
		mobileLabel: 'Freq.',
		cell: (kanji) => <NumberValue value={presentRank(kanji.frequency)} missingLabel="No frequency rank" />,
	},
];

const stackedAreas = [
	'glyph meaning meaning meaning',
	'glyph on on on',
	'glyph kun kun kun',
	'glyph strokes jlpt freq',
];

/** Strokes, JLPT and Freq. share the fourth line; Save takes its own column on the right. */
const STACKED = { columns: '48px auto auto minmax(0, 1fr)', areas: stackedAreas };
const STACKED_WITH_SAVE = {
	columns: '48px auto auto minmax(0, 1fr) auto',
	areas: stackedAreas.map((area) => `${area} save`),
};

/**
 * The Kanji list as an Index table: glyph, first three meanings, readings, strokes, level and
 * frequency, plus Save for signed-in viewers. Below 768px each row stacks as glyph | details | Save.
 */
export const KanjiTable: React.FC<KanjiTableProps> = ({ kanjis, showSave, loading, empty, onBookmarkStateChange }) => {
	const columns: DataTableColumn<KanjiResource>[] = showSave
		? [
				...baseColumns,
				{
					id: 'save',
					header: 'Save',
					headerHidden: true,
					width: 'shrink',
					cellClassName: styles.actionCell,
					cell: (kanji) => (
						<AuthorizedBookmarkWidget
							compact
							itemLabel={kanji.character}
							instanceObjectType={SavedListType.KANJIS}
							isKnownType={SavedListType.KNOWNKANJIS}
							entityId={kanji.id}
							modalTitle="Choose Kanji List to add"
							initialIsBookmarked={kanji.viewer_catalogue_state?.is_saved ?? false}
							initialIsKnown={kanji.viewer_catalogue_state?.is_known ?? false}
							loadOnMount={false}
							onStateChange={(state) =>
								onBookmarkStateChange?.(kanji.id, {
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
			label="Kanji"
			columns={columns}
			rows={kanjis}
			getRowKey={(kanji) => kanji.uuid}
			loading={loading}
			empty={empty}
			stacked={showSave ? STACKED_WITH_SAVE : STACKED}
		/>
	);
};

export default KanjiTable;

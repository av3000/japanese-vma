import * as React from 'react';
import type { RadicalResource } from '@/api/generated/model';
import {
	GlyphLink,
	JapaneseText,
	NumberValue,
	PlainText,
	presentRank,
	presentText,
	withTrailingAreas,
	withTrailingColumns,
} from '@/components/features/japanese/dictionaryList';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import styles from './RadicalTable.module.css';
import { splitRadicalGlyph, splitRadicalReading } from './radicalValues';

export interface RadicalTableProps {
	radicals: RadicalResource[];
	loading?: boolean;
	empty: DataTableEmpty;
	/** Columns appended after the table's own, e.g. the owner's Remove column on Catalogue detail. */
	trailingColumns?: DataTableColumn<RadicalResource>[];
}

/**
 * A radical without a glyph still needs a link name that tells rows apart: its meaning when it
 * has one, otherwise its id.
 */
const missingGlyphLabel = (radical: RadicalResource): string => {
	const meaning = presentText(radical.meaning);

	return meaning ? `Radical without a glyph: ${meaning}` : `Radical without a glyph, #${radical.id}`;
};

/** Every radical field is nullable, so every cell has a labelled dash. */
const columns: DataTableColumn<RadicalResource>[] = [
	{
		id: 'glyph',
		header: 'Radical',
		rowHeader: true,
		width: 'shrink',
		cellClassName: styles.glyphCell,
		cell: (radical) => {
			const { glyph, variants } = splitRadicalGlyph(radical.radical);

			return (
				<>
					<GlyphLink
						to={`/radical/${radical.uuid}`}
						glyph={glyph}
						missingLabel={missingGlyphLabel(radical)}
					/>
					{variants.length > 0 ? (
						<span className={styles.variants}>
							<span className={styles.visuallyHidden}>Variants: </span>
							<span lang="ja">{variants.join(' ')}</span>
						</span>
					) : null}
				</>
			);
		},
	},
	{
		id: 'meaning',
		header: 'Meaning',
		width: 'fill',
		cellClassName: styles.lead,
		cell: (radical) => <PlainText value={presentText(radical.meaning)} missingLabel="No meaning" />,
	},
	{
		id: 'reading',
		header: 'Reading',
		mobileLabel: 'Reading',
		cell: (radical) => {
			const { kana, romaji } = splitRadicalReading(radical.hiragana);

			return (
				<>
					<JapaneseText value={kana} missingLabel="No reading" />
					{romaji ? <span className={styles.romaji}>{romaji}</span> : null}
				</>
			);
		},
	},
	{
		id: 'strokes',
		header: 'Strokes',
		numeric: true,
		mobileLabel: 'Strokes',
		cell: (radical) => <NumberValue value={presentRank(radical.strokes)} missingLabel="No stroke count" />,
	},
];

/** Glyph | meaning, reading, strokes in stacked rows. */
const STACKED = { columns: '48px minmax(0, 1fr)', areas: ['glyph meaning', 'glyph reading', 'glyph strokes'] };

/** The Radicals list as an Index table: the same table as the other lists, not a glyph grid. */
export const RadicalTable: React.FC<RadicalTableProps> = ({ radicals, loading, empty, trailingColumns }) => (
	<DataTable
		label="Radicals"
		columns={withTrailingColumns(columns, trailingColumns)}
		rows={radicals}
		getRowKey={(radical) => radical.uuid}
		loading={loading}
		empty={empty}
		stacked={withTrailingAreas(STACKED, trailingColumns)}
		className={styles.table}
	/>
);

export default RadicalTable;

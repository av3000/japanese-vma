import * as React from 'react';
import type { SentenceResource } from '@/api/generated/model';
import { TextLink, presentText } from '@/components/features/japanese/dictionaryList';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import styles from './SentenceTable.module.css';

export interface SentenceTableProps {
	sentences: SentenceResource[];
	loading?: boolean;
	empty: DataTableEmpty;
}

export const tatoebaUrl = (entry: string) => `https://tatoeba.org/eng/sentences/show/${encodeURIComponent(entry)}`;

const columns: DataTableColumn<SentenceResource>[] = [
	{
		id: 'sentence',
		header: 'Sentence',
		rowHeader: true,
		width: 'fill',
		cell: (sentence) => (
			<TextLink to={`/sentence/${sentence.uuid}`} variant="sentence">
				{sentence.content}
			</TextLink>
		),
	},
	{
		id: 'source',
		header: 'Source',
		mobileLabel: 'Source',
		width: 'shrink',
		cellClassName: styles.source,
		cell: (sentence) => {
			const entry = presentText(sentence.tatoeba_entry);

			return entry ? (
				<a href={tatoebaUrl(entry)} target="_blank" rel="noopener noreferrer" className={styles.sourceLink}>
					Tatoeba #{entry}
				</a>
			) : (
				'Added by a user'
			);
		},
	},
];

/**
 * The Sentences list as an Index table: the sentence (the detail link) and its source. The list
 * payload carries no translation, so there is no translation column. Rows stack one cell per line
 * below 768px.
 */
export const SentenceTable: React.FC<SentenceTableProps> = ({ sentences, loading, empty }) => (
	<DataTable
		label="Sentences"
		columns={columns}
		rows={sentences}
		getRowKey={(sentence) => sentence.uuid}
		loading={loading}
		empty={empty}
	/>
);

export default SentenceTable;

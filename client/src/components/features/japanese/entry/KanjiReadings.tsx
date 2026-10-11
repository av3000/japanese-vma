import * as React from 'react';
import { presentAllValues } from '@/components/features/japanese/dictionaryList';
import { kunReadingParts } from '@/components/features/japanese/entryValues';
import styles from './Entry.module.css';

interface KanjiReadingsProps {
	onyomi: readonly string[];
	kunyomi: readonly string[];
	nanori: readonly string[];
}

/** A kun'yomi with its okurigana set apart by a dot and a lighter colour, so colour is not the only cue. */
const KunReading: React.FC<{ value: string }> = ({ value }) => {
	const parts = kunReadingParts(value);

	if (!parts) return null;

	return (
		<>
			{parts.prefix}
			{parts.stem}
			{parts.okurigana ? (
				<span className={styles.okurigana}>
					<span aria-hidden="true">·</span>
					{parts.okurigana}
				</span>
			) : null}
			{parts.suffix}
		</>
	);
};

const ReadingList: React.FC<{ values: string[]; render?: (value: string) => React.ReactNode }> = ({
	values,
	render = (value) => value,
}) => (
	<ul className={styles.readingList} lang="ja">
		{values.map((value) => (
			<li key={value}>{render(value)}</li>
		))}
	</ul>
);

/**
 * A kanji's readings as a definition list: on'yomi, kun'yomi and the readings used in names. A
 * reading the dictionary does not have is left out; with none at all, one line says so.
 */
export const KanjiReadings: React.FC<KanjiReadingsProps> = ({ onyomi, kunyomi, nanori }) => {
	const headingId = React.useId();
	const rows = [
		{ term: 'On’yomi', values: presentAllValues(onyomi), kun: false },
		{ term: 'Kun’yomi', values: presentAllValues(kunyomi), kun: true },
		{ term: 'Name readings', values: presentAllValues(nanori), kun: false },
	].filter((row) => row.values.length > 0);

	return (
		<section className={styles.block} aria-labelledby={headingId}>
			<h2 id={headingId} className={styles.blockHeading}>
				Readings
			</h2>
			{rows.length === 0 ? (
				<p className={styles.missing}>No readings are recorded for this kanji.</p>
			) : (
				<dl className={styles.definitions}>
					{rows.map((row) => (
						<div key={row.term} className={styles.definition}>
							<dt className={styles.term}>{row.term}</dt>
							<dd className={styles.value}>
								<ReadingList
									values={row.values}
									render={row.kun ? (value) => <KunReading value={value} /> : undefined}
								/>
							</dd>
						</div>
					))}
				</dl>
			)}
		</section>
	);
};

export default KanjiReadings;

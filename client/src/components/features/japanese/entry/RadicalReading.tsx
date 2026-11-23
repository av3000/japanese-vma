import * as React from 'react';
import type { RadicalReadingParts } from '@/components/features/japanese/entryValues';
import styles from './Entry.module.css';

interface RadicalReadingProps {
	reading: RadicalReadingParts | null;
	/** Other forms the radical takes inside a kanji, e.g. 氵 and 氺 for 水. */
	variants: readonly string[];
}

/** A radical's name in kana with its romaji, and the forms it takes inside other kanji. */
export const RadicalReading: React.FC<RadicalReadingProps> = ({ reading, variants }) => {
	const headingId = React.useId();

	return (
		<section className={styles.block} aria-labelledby={headingId}>
			<h2 id={headingId} className={styles.blockHeading}>
				Reading
			</h2>
			{reading || variants.length > 0 ? (
				<dl className={styles.definitions}>
					{reading ? (
						<div className={styles.definition}>
							<dt className={styles.term}>Name</dt>
							<dd className={styles.value}>
								<span className={styles.readingList} lang="ja">
									{reading.kana}
								</span>
								{reading.romaji ? <span className={styles.romaji}> {reading.romaji}</span> : null}
							</dd>
						</div>
					) : null}
					{variants.length > 0 ? (
						<div className={styles.definition}>
							<dt className={styles.term}>Variants</dt>
							<dd className={styles.value}>
								<ul className={styles.readingList} lang="ja">
									{variants.map((variant) => (
										<li key={variant} className={styles.glyphs}>
											{variant}
										</li>
									))}
								</ul>
							</dd>
						</div>
					) : null}
				</dl>
			) : (
				<p className={styles.missing}>No reading is recorded for this radical.</p>
			)}
		</section>
	);
};

export default RadicalReading;

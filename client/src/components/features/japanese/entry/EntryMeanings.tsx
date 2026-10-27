import * as React from 'react';
import type { WordSenseResource } from '@/api/generated/model/wordSenseResource';
import styles from './Entry.module.css';

interface EntryMeaningsProps {
	/** The word's numbered senses, in dictionary order. */
	senses: readonly WordSenseResource[];
	/** Every gloss, already without the API's placeholders: shown when there are no senses. */
	meanings: readonly string[];
}

/**
 * A word's meanings as numbered senses. Each sense shows its part of speech when it differs from
 * the sense before, then its glosses, then its usage notes, both muted. A word whose dictionary
 * entry has no senses falls back to every gloss in one paragraph.
 */
export const EntryMeanings: React.FC<EntryMeaningsProps> = ({ senses, meanings }) => {
	const headingId = React.useId();

	return (
		<section className={styles.block} aria-labelledby={headingId}>
			<h2 id={headingId} className={styles.blockHeading}>
				Meanings
			</h2>
			{senses.length > 0 ? (
				<ol className={styles.senses} lang="en">
					{senses.map((sense, index) => {
						const partsOfSpeech = sense.parts_of_speech.join(', ');
						const previous = index > 0 ? senses[index - 1].parts_of_speech.join(', ') : null;
						const usage = [...sense.misc, ...sense.notes];

						return (
							<li key={index} className={styles.sense}>
								{partsOfSpeech && partsOfSpeech !== previous ? (
									<p className={styles.senseMeta}>{partsOfSpeech}</p>
								) : null}
								<p className={styles.meanings}>{sense.glosses.join('; ')}</p>
								{usage.length > 0 ? <p className={styles.senseMeta}>{usage.join('; ')}</p> : null}
							</li>
						);
					})}
				</ol>
			) : meanings.length > 0 ? (
				<p className={styles.meanings} lang="en">
					{meanings.join('; ')}
				</p>
			) : (
				<p className={styles.missing}>No English meaning recorded.</p>
			)}
		</section>
	);
};

export default EntryMeanings;

import * as React from 'react';
import styles from './Entry.module.css';

interface EntryMeaningsProps {
	/** Every gloss, already without the API's placeholders. */
	meanings: readonly string[];
}

/**
 * A word's meanings in one paragraph, separated by semicolons. The API sends the glosses flat, so
 * they cannot be grouped by sense until it sends senses as data (#547).
 */
export const EntryMeanings: React.FC<EntryMeaningsProps> = ({ meanings }) => {
	const headingId = React.useId();

	return (
		<section className={styles.block} aria-labelledby={headingId}>
			<h2 id={headingId} className={styles.blockHeading}>
				Meanings
			</h2>
			{meanings.length > 0 ? (
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

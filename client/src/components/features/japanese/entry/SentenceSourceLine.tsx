import * as React from 'react';
import type { SentenceSource } from '@/components/features/japanese/entryValues';
import { Link } from '@/components/shared/Link';
import styles from './Entry.module.css';

/** Where a sentence comes from, under its text: a link to its Tatoeba entry, or who wrote it. */
export const SentenceSourceLine: React.FC<{ source: SentenceSource }> = ({ source }) => {
	switch (source.kind) {
		case 'tatoeba':
			return (
				<p className={styles.source}>
					From{' '}
					<Link linkUrl={source.href} rel="noopener noreferrer">
						{`Tatoeba #${source.entry}`}
					</Link>
				</p>
			);
		case 'own':
			return <p className={styles.source}>Written by you</p>;
		case 'member':
			return <p className={styles.source}>Written by a member</p>;
	}
};

export default SentenceSourceLine;

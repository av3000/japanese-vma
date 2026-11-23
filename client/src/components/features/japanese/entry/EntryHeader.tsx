import * as React from 'react';
import classNames from 'classnames';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import styles from './Entry.module.css';

export interface EntryHeaderProps {
	/** The dictionary list this entry belongs to, e.g. `/kanjis`. */
	backTo: string;
	/** The list's name on the back link, e.g. "Kanji". */
	backLabel: string;
	/** The entry itself: a glyph, a word or a sentence. */
	title: string;
	/**
	 * `glyph` for a single kanji or radical at display size, `word` for a word, `sentence` for a
	 * sentence at reading size, so a 300-character title still reads as text.
	 */
	kind: 'glyph' | 'word' | 'sentence';
	/** The kana reading under a word, when it differs from the word. */
	reading?: string | null;
	/** The English meaning under the title. `null` shows that none is recorded. */
	lead?: string | null;
	/** Under the lead, e.g. a sentence's source. */
	children?: React.ReactNode;
}

/**
 * The top of a dictionary detail page (#352): the way back to its list, the entry as the page's
 * `h1` in the Japanese serif face, its reading and its English meaning.
 */
export const EntryHeader: React.FC<EntryHeaderProps> = ({
	backTo,
	backLabel,
	title,
	kind,
	reading,
	lead,
	children,
}) => (
	<div className={styles.header}>
		<Link to={backTo} className={styles.back}>
			<Icon name="arrowDownSolid" rotate="90" size="sm" /> {backLabel}
		</Link>
		<h1 className={classNames(styles.title, styles[kind])} lang="ja">
			{title}
		</h1>
		{reading ? (
			<p className={styles.reading} lang="ja">
				{reading}
			</p>
		) : null}
		{lead !== undefined ? (
			<p className={classNames(styles.lead, lead === null && styles.missing)} lang="en">
				{lead ?? 'No English meaning recorded.'}
			</p>
		) : null}
		{children}
	</div>
);

export default EntryHeader;

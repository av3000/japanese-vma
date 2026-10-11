import * as React from 'react';
import type { KanjiRadical } from '@/components/features/japanese/entryValues';
import { Link } from '@/components/shared/Link';
import styles from './Entry.module.css';

/** A kanji's radicals as links to their pages: the glyph, then its Kangxi number. */
export const RadicalLinks: React.FC<{ radicals: readonly KanjiRadical[] }> = ({ radicals }) => (
	<>
		{radicals.map((radical, index) => (
			<React.Fragment key={radical.number}>
				{index > 0 ? ' ' : null}
				<Link to={radical.href}>
					<span lang="ja">{radical.glyph}</span>{' '}
					<span className={styles.radicalNumber}>No. {radical.number}</span>
				</Link>
			</React.Fragment>
		))}
	</>
);

export default RadicalLinks;

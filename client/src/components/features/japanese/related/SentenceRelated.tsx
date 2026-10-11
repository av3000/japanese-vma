import * as React from 'react';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { RelatedSection } from '@/components/features/japanese/dictionaryList';
import { EntryKanji } from './EntryKanji';
import styles from './Related.module.css';

interface SentenceRelatedProps {
	sentence: { id: number; uuid: string; content: string; kanjis: KanjiResource[] };
}

/** Under a sentence: the kanji it is written with, then its comments. */
export const SentenceRelated: React.FC<SentenceRelatedProps> = ({ sentence }) => (
	<div className={styles.sections}>
		<EntryKanji
			title="Kanji in this sentence"
			text={sentence.content}
			kanjis={sentence.kanjis}
			emptyMessage="This sentence is written without kanji."
		/>
		<RelatedSection title="Comments">
			{/* A sentence cannot be locked, so no `isLocked` is passed; the gate exists only for Post. */}
			<CommentsBlock parent="sentence" entityId={sentence.id} entityUuid={sentence.uuid} />
		</RelatedSection>
	</div>
);

export default SentenceRelated;

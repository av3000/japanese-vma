import * as React from 'react';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import { RelatedSection } from '@/components/features/japanese/dictionaryList';
import { inTextOrder } from '@/components/features/japanese/entryValues';

interface EntryKanjiProps {
	/** E.g. "Kanji in this word". */
	title: string;
	/** The word or sentence the kanji come from; they are listed in the order they appear in it. */
	text: string;
	kanjis: KanjiResource[];
	emptyMessage: string;
}

/**
 * The kanji a word or sentence is written with, straight from its payload: a word has at most 18 and
 * a sentence at most 59, so the whole list fits on the page. The payload has no viewer save state,
 * so there is no Save column; each kanji's own page has Save.
 */
export const EntryKanji: React.FC<EntryKanjiProps> = ({ title, text, kanjis, emptyMessage }) => (
	<RelatedSection title={title} count={kanjis.length}>
		<KanjiTable kanjis={inTextOrder(kanjis, text)} showSave={false} empty={{ title: emptyMessage }} />
	</RelatedSection>
);

export default EntryKanji;

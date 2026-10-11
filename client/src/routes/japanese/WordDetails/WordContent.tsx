import type { MappedWordDetail } from '@/api/words/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import {
	presentAllValues,
	presentText,
	presentValues,
	toJlptLevel,
} from '@/components/features/japanese/dictionaryList';
import { EntryHeader, EntryMeanings } from '@/components/features/japanese/entry';
import { withoutMissing } from '@/components/features/japanese/entryValues';
import { WordRelated } from '@/components/features/japanese/related';
import { DetailActions } from '@/components/shared/DetailActions';
import { DetailFacts, type DetailFact } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface WordContentProps {
	word: MappedWordDetail;
}

/** The rail's facts, leaving out any the dictionary does not have rather than showing a dash. */
const wordFacts = (word: MappedWordDetail): DetailFact[] => {
	const partsOfSpeech = presentAllValues(word.word_types);
	const level = toJlptLevel(word.jlpt);

	return withoutMissing<DetailFact>([
		partsOfSpeech.length > 0 ? { term: 'Part of speech', value: { text: partsOfSpeech.join(', ') } } : null,
		level ? { term: 'JLPT', value: { text: <LevelBadge level={level} size="sm" /> } } : null,
		{ term: 'Kanji', value: word.kanjis.length },
	]);
};

const WordContent = ({ word }: WordContentProps) => {
	const { isAuthenticated } = useAuth();
	const reading = presentText(word.furigana);
	const lead = presentValues(word.meanings);

	return (
		<DetailLayout
			railLabel="About this word"
			header={
				<EntryHeader
					backTo="/words"
					backLabel="Words"
					title={word.word}
					kind="word"
					reading={reading && reading !== word.word ? reading : null}
					lead={lead.length > 0 ? lead.join(', ') : null}
				/>
			}
			main={<EntryMeanings meanings={presentAllValues(word.meanings)} />}
			facts={<DetailFacts title="About this word" facts={wordFacts(word)} />}
			actions={
				isAuthenticated ? (
					<DetailActions>
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.WORDS}
							isKnownType={SavedListType.KNOWNWORDS}
							entityId={word.id}
							modalTitle="Choose Word List to add"
							label="Save to a catalogue"
							savedLabel="Saved to a catalogue"
						/>
					</DetailActions>
				) : undefined
			}
			after={<WordRelated word={word} />}
		/>
	);
};

export default WordContent;

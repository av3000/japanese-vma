import type { MappedKanji } from '@/api/kanjis/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { presentAllValues, presentRank, toJlptLevel } from '@/components/features/japanese/dictionaryList';
import { EntryHeader, KanjiReadings, RadicalLinks } from '@/components/features/japanese/entry';
import {
	frequencyLabel,
	kanjiGradeLabel,
	kanjiParts,
	kanjiRadicals,
	withoutMissing,
} from '@/components/features/japanese/entryValues';
import { KanjiRelated } from '@/components/features/japanese/related';
import { DetailActions } from '@/components/shared/DetailActions';
import { DetailFacts, type DetailFact } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface KanjiContentProps {
	kanji: MappedKanji;
}

/** The rail's facts, leaving out any the dictionary does not have rather than showing a dash. */
const kanjiFacts = (kanji: MappedKanji): DetailFact[] => {
	const strokes = presentRank(kanji.stroke_count);
	const level = toJlptLevel(kanji.jlpt);
	const grade = kanjiGradeLabel(kanji.grade);
	const frequency = frequencyLabel(kanji.frequency);
	const radicals = kanjiRadicals(kanji.radicals);
	const parts = kanjiParts(kanji.radical_parts);

	return withoutMissing<DetailFact>([
		strokes === null ? null : { term: 'Strokes', value: strokes },
		level ? { term: 'JLPT', value: { text: <LevelBadge level={level} size="sm" /> } } : null,
		grade ? { term: 'Grade', value: { text: grade } } : null,
		frequency ? { term: 'Frequency', value: { text: frequency } } : null,
		radicals.length > 0
			? {
					term: radicals.length === 1 ? 'Radical' : 'Radicals',
					value: { text: <RadicalLinks radicals={radicals} /> },
				}
			: null,
		parts.length > 0 ? { term: 'Parts', value: { text: <span lang="ja">{parts.join(' ')}</span> } } : null,
	]);
};

const KanjiContent = ({ kanji }: KanjiContentProps) => {
	const { isAuthenticated } = useAuth();
	const meanings = presentAllValues(kanji.meanings);

	return (
		<DetailLayout
			railLabel="About this kanji"
			header={
				<EntryHeader
					backTo="/kanjis"
					backLabel="Kanji"
					title={kanji.character}
					kind="glyph"
					lead={meanings.length > 0 ? meanings.join(', ') : null}
				/>
			}
			main={<KanjiReadings onyomi={kanji.onyomi} kunyomi={kanji.kunyomi} nanori={kanji.nanori} />}
			facts={<DetailFacts title="About this kanji" facts={kanjiFacts(kanji)} />}
			actions={
				isAuthenticated ? (
					<DetailActions>
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.KANJIS}
							isKnownType={SavedListType.KNOWNKANJIS}
							entityId={kanji.id}
							modalTitle="Choose Kanji List to add"
							initialIsBookmarked={kanji.viewer_catalogue_state?.is_saved ?? false}
							initialIsKnown={kanji.viewer_catalogue_state?.is_known ?? false}
							loadOnMount={false}
							label="Save to a catalogue"
							savedLabel="Saved to a catalogue"
						/>
					</DetailActions>
				) : undefined
			}
			after={<KanjiRelated kanji={kanji} showSave={isAuthenticated} />}
		/>
	);
};

export default KanjiContent;

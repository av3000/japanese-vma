import type { RadicalResource } from '@/api/generated/model/radicalResource';
import { useRadicalKanjiPages } from '@/api/radicals/related';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { presentRank, presentText } from '@/components/features/japanese/dictionaryList';
import { EntryHeader, RadicalReading } from '@/components/features/japanese/entry';
import { radicalGlyphParts, radicalReadingParts, withoutMissing } from '@/components/features/japanese/entryValues';
import { RadicalKanji } from '@/components/features/japanese/related';
import { DetailActions } from '@/components/shared/DetailActions';
import { DetailFacts, type DetailFact } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface RadicalContentProps {
	radical: RadicalResource;
}

const RadicalContent = ({ radical }: RadicalContentProps) => {
	const { isAuthenticated } = useAuth();
	// Page 1 of the radical's kanji, the same query the list below reads, for the count in the rail.
	const kanji = useRadicalKanjiPages(radical.uuid);
	const { glyph, variants } = radicalGlyphParts(radical.radical);
	const strokes = presentRank(radical.strokes);

	const facts = withoutMissing<DetailFact>([
		// A radical's id is its Kangxi number, 1 to 214.
		{ term: 'Number', value: { text: `No. ${radical.id}` } },
		strokes === null ? null : { term: 'Strokes', value: strokes },
		kanji.isError ? null : { term: 'Kanji using it', value: kanji.isPending ? null : kanji.total },
	]);

	return (
		<DetailLayout
			railLabel="About this radical"
			header={
				<EntryHeader
					backTo="/radicals"
					backLabel="Radicals"
					title={glyph ?? '—'}
					kind="glyph"
					lead={presentText(radical.meaning)}
				/>
			}
			main={<RadicalReading reading={radicalReadingParts(radical.hiragana)} variants={variants} />}
			facts={<DetailFacts title="About this radical" facts={facts} />}
			actions={
				isAuthenticated ? (
					<DetailActions>
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.RADICALS}
							isKnownType={SavedListType.KNOWNRADICALS}
							entityId={radical.id}
							modalTitle="Choose Radical List to add"
							label="Save to a catalogue"
							savedLabel="Saved to a catalogue"
						/>
					</DetailActions>
				) : undefined
			}
			after={<RadicalKanji radicalUuid={radical.uuid} showSave={isAuthenticated} />}
		/>
	);
};

export default RadicalContent;

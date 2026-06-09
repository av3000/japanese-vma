import type { MappedKanji } from '@/api/kanjis/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface KanjiContentProps {
	kanji: MappedKanji;
}

const KanjiContent = ({ kanji }: KanjiContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<div className="container">
			<div className="mt-4">
				<Link to="/kanjis" className="tag-link">
					Back
				</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-4">
					<h1>{kanji.character}</h1>
					<p>Meaning: {kanji.display.meaning}</p>
				</div>
				<div className="col-md-4">
					<p>Onyomi: {kanji.display.onyomi}</p>
					<p>Kunyomi: {kanji.display.kunyomi}</p>
				</div>
				<div className="col-md-2">
					<p>Parts: {kanji.display.radicalParts}</p>
					<p>Strokes: {kanji.stroke_count}</p>
				</div>
				<div className="col-md-2">
					<p>JLPT: {kanji.display.jlpt}</p>
					<p>Frequency: {kanji.display.frequency}</p>
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.KANJIS}
							isKnownType={SavedListType.KNOWNKANJIS}
							entityId={kanji.id}
							modalTitle="Choose Kanji List to add"
						/>
					)}
				</div>
			</div>
		</div>
	);
};

export default KanjiContent;

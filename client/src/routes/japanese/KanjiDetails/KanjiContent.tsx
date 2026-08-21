import type { MappedKanji } from '@/api/kanjis/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import KanjiRelatedArticles from '@/components/features/japanese/Kanji/KanjiRelatedArticles';
import KanjiRelatedSentences from '@/components/features/japanese/Kanji/KanjiRelatedSentences';
import KanjiRelatedWords from '@/components/features/japanese/Kanji/KanjiRelatedWords';
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
			<div className="row mt-5">
				<div className="col-md-4">
					<h1>{kanji.character}</h1>
					<p>Kunyomi: {kanji.display.kunyomi}</p>
					<p>Onyomi: {kanji.display.onyomi}</p>
				</div>
				<div className="col-md-4">
					<h2>{kanji.display.meaning}</h2>
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
							initialIsBookmarked={kanji.viewer_catalogue_state?.is_saved ?? false}
							initialIsKnown={kanji.viewer_catalogue_state?.is_known ?? false}
							loadOnMount={false}
						/>
					)}
				</div>
			</div>
			<KanjiRelatedWords items={kanji.related.words} total={kanji.related.wordTotal} />
			<KanjiRelatedSentences items={kanji.related.sentences} total={kanji.related.sentenceTotal} />
			<KanjiRelatedArticles items={kanji.related.articles} total={kanji.related.articleTotal} />
		</div>
	);
};

export default KanjiContent;

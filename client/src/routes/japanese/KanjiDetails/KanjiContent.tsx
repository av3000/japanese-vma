import type { MappedKanji } from '@/api/kanjis/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import KanjiRelatedArticles from '@/components/features/japanese/Kanji/KanjiRelatedArticles';
import KanjiRelatedSentences from '@/components/features/japanese/Kanji/KanjiRelatedSentences';
import KanjiRelatedWords from '@/components/features/japanese/Kanji/KanjiRelatedWords';
import { Link } from '@/components/shared/Link';
import { Container, Grid, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';
import styles from '../japaneseDetailPage.module.css';

interface KanjiContentProps {
	kanji: MappedKanji;
}

const KanjiContent = ({ kanji }: KanjiContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<div>
					<Link to="/kanjis">Back</Link>
				</div>
				<Grid columns={12} gap="lg">
					<Grid.Item span={{ base: 12, sm: 4 }}>
						<h1>{kanji.character}</h1>
						<p>Kunyomi: {kanji.display.kunyomi}</p>
						<p>Onyomi: {kanji.display.onyomi}</p>
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 4 }}>
						<h2>{kanji.display.meaning}</h2>
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 2 }}>
						<p>Parts: {kanji.display.radicalParts}</p>
						<p>Strokes: {kanji.stroke_count}</p>
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 2 }}>
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
					</Grid.Item>
				</Grid>
				<KanjiRelatedWords items={kanji.related.words} total={kanji.related.wordTotal} />
				<KanjiRelatedSentences items={kanji.related.sentences} total={kanji.related.sentenceTotal} />
				<KanjiRelatedArticles items={kanji.related.articles} total={kanji.related.articleTotal} />
			</Stack>
		</Container>
	);
};

export default KanjiContent;

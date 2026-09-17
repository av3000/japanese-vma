import classNames from 'classnames';
import type { MappedWordDetail } from '@/api/words/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Chip } from '@/components/shared/Chip';
import { Link } from '@/components/shared/Link';
import { Cluster, Container, Grid, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';
import styles from '../japaneseDetailPage.module.css';

interface WordContentProps {
	word: MappedWordDetail;
}

const WordContent = ({ word }: WordContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<div>
					<Link to="/words">Back</Link>
				</div>
				<Grid columns={12} gap="lg">
					<Grid.Item span={{ base: 12, sm: 4 }}>
						<h1 lang="ja">{word.word}</h1>
						<p>
							Furigana: <span lang="ja">{word.furigana}</span>
						</p>
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 4 }}>
						<p>Type: {word.word_type}</p>
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 4 }}>
						<p>
							JLPT: {word.jlpt} <br /> Meaning: {word.meanings.join(', ')}
						</p>
						{isAuthenticated && (
							<AuthorizedBookmarkWidget
								instanceObjectType={SavedListType.WORDS}
								isKnownType={SavedListType.KNOWNWORDS}
								entityId={word.id}
								modalTitle="Choose Word List to add"
							/>
						)}
					</Grid.Item>
				</Grid>

				<section className={styles.section}>
					<h4>Kanjis ({word.kanjis.length}) results</h4>
					<ul className={styles.relatedList}>
						{word.kanjis.map((kanji) => (
							<li className={styles.relatedRow} key={kanji.uuid}>
								<h3 lang="ja">{kanji.character}</h3>
								<span>{kanji.meanings.slice(0, 3).join(', ')}</span>
								<Link to={`/kanji/${kanji.uuid}`} className={styles.rowAction}>
									Open
								</Link>
							</li>
						))}
					</ul>
				</section>

				<section className={styles.section}>
					<h4>Articles ({word.articles.length}) results</h4>
					<ul className={styles.relatedList}>
						{word.articles.map((article) => (
							<li className={classNames(styles.relatedRow, styles.relatedRowWide)} key={article.uuid}>
								<div>
									<h3 lang="ja">{article.title_jp}</h3>
									<Cluster gap="2xs">
										{article.hashtags.map((tag) => (
											<Chip readonly key={tag.id} title={tag.content} name={tag.content}>
												{tag.content}
											</Chip>
										))}
									</Cluster>
								</div>
								<p>
									Views: {article.views_total} <br /> Likes: {article.likes_total} <br /> Comments:{' '}
									{article.comments_total}
								</p>
								<Link to={`/articles/${article.uuid}`} className={styles.rowAction} target="_blank">
									Open
								</Link>
							</li>
						))}
					</ul>
				</section>
			</Stack>
		</Container>
	);
};

export default WordContent;

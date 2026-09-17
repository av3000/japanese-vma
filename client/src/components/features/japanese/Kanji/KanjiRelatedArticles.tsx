import type { MappedKanji } from '@/api/kanjis/details';
import { Link } from '@/components/shared/Link';
import styles from './KanjiRelatedResources.module.css';

interface KanjiRelatedArticlesProps {
	items: MappedKanji['related']['articles'];
	total: number;
}

const KanjiRelatedArticles = ({ items, total }: KanjiRelatedArticlesProps) => (
	<section>
		<h2>Found in ({total}) articles</h2>
		{items.length === 0 ? (
			<p>No related articles found.</p>
		) : (
			<ul className={styles.relatedList}>
				{items.map((article) => (
					<li className={styles.relatedResource} key={article.uuid}>
						<div>
							<h3 lang="ja">{article.title_jp}</h3>
							{article.hashtags.length > 0 && (
								<p>{article.hashtags.map((hashtag) => hashtag.content).join(' ')}</p>
							)}
							<p>
								Likes: {article.likes_total} · Views: {article.views_total} · Comments:{' '}
								{article.comments_total}
							</p>
						</div>
						<Link className={styles.openLink} to={`/articles/${article.uuid}`}>
							Open
						</Link>
					</li>
				))}
			</ul>
		)}
	</section>
);

export default KanjiRelatedArticles;

import type { MappedKanji } from '@/api/kanjis/details';
import { Link } from '@/components/shared/Link';
import styles from './KanjiRelatedResources.module.scss';

interface KanjiRelatedArticlesProps {
	items: MappedKanji['related']['articles'];
	total: number;
}

const KanjiRelatedArticles = ({ items, total }: KanjiRelatedArticlesProps) => (
	<section className="mt-5 mb-5">
		<h2>Found in ({total}) articles</h2>
		{items.length === 0 ? (
			<p>No related articles found.</p>
		) : (
			items.map((article) => {
				const stats = article.engagement.stats;

				return (
					<div
						className={`${styles.relatedResource} post-preview d-flex justify-content-between align-items-start`}
						key={article.uuid}
					>
						<div>
							<h3>{article.title_jp}</h3>
							{article.hashtags.length > 0 && (
								<p>{article.hashtags.map((hashtag) => hashtag.content).join(' ')}</p>
							)}
							{stats !== null && (
								<p>
									Likes: {stats.likes_count} · Views: {stats.views_count} · Comments:{' '}
									{stats.comments_count}
								</p>
							)}
						</div>
						<Link className="ml-3 flex-shrink-0" to={`/articles/${article.uuid}`}>
							Open
						</Link>
					</div>
				);
			})
		)}
	</section>
);

export default KanjiRelatedArticles;

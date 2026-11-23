import * as React from 'react';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import { Link } from '@/components/shared/Link';
import { japaneseLang } from '@/helpers/japaneseLang';
import styles from './Related.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

const count = (amount: number, singular: string, plural: string) =>
	`${numberFormat.format(amount)} ${amount === 1 ? singular : plural}`;

interface RelatedArticleListProps {
	articles: ArticleResource[];
}

/** Articles as a compact list: the title linking to the article, then its likes and views as text. */
export const RelatedArticleList: React.FC<RelatedArticleListProps> = ({ articles }) => (
	<ul className={styles.articles}>
		{articles.map((article) => {
			const stats = article.engagement?.stats;

			return (
				<li key={article.uuid} className={styles.article}>
					<Link
						to={`/articles/${article.uuid}`}
						className={styles.articleTitle}
						lang={japaneseLang(article.title_jp)}
					>
						{article.title_jp}
					</Link>
					<span className={styles.articleMeta}>
						{count(Number(stats?.likes_count ?? 0), 'like', 'likes')} ·{' '}
						{count(Number(stats?.views_count ?? 0), 'view', 'views')}
					</span>
				</li>
			);
		})}
	</ul>
);

export default RelatedArticleList;

import React from 'react';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import { Button } from '@/components/shared/Button';
import type { DataTableEmpty } from '@/components/shared/DataTable';
import { Link } from '@/components/shared/Link';
import styles from './CatalogueItems.module.css';

const numberFormat = new Intl.NumberFormat('en-US');

// Engagement counts arrive as strings in this payload.
const count = (value: number | string | null | undefined, singular: string, plural: string) => {
	const amount = Number(value ?? 0);

	return `${numberFormat.format(amount)} ${amount === 1 ? singular : plural}`;
};

interface CatalogueArticleListProps {
	articles: CatalogueArticleItem[];
	empty: DataTableEmpty;
	/** Only while the owner manages the catalogue: adds a Remove button to every row. */
	onRemove?: (article: CatalogueArticleItem) => void;
}

/**
 * An article catalogue's items as a compact list: the Japanese title linking to the article, its
 * tags as plain text, and its likes and views. Article catalogues still read the detail payload.
 */
export const CatalogueArticleList: React.FC<CatalogueArticleListProps> = ({ articles, empty, onRemove }) => {
	if (articles.length === 0) {
		return (
			<div className={styles.empty} role="status">
				<p className={styles.emptyTitle}>{empty.title}</p>
				{empty.hint ? <p className={styles.emptyHint}>{empty.hint}</p> : null}
			</div>
		);
	}

	return (
		<ul className={styles.articleList} aria-label="Articles">
			{articles.map((article) => (
				<li key={article.id} className={styles.articleRow}>
					<div className={styles.articleText}>
						<Link to={`/articles/${article.uuid}`} lang="ja" className={styles.articleTitle}>
							{article.title_jp}
						</Link>
						{article.hashtags.length > 0 ? (
							<p className={styles.articleTags}>
								{article.hashtags.map((tag) => tag.content).join(' · ')}
							</p>
						) : null}
						<p className={styles.articleMeta}>
							{count(article.engagement?.likes_count, 'like', 'likes')} ·{' '}
							{count(article.engagement?.views_count, 'view', 'views')}
						</p>
					</div>
					{onRemove ? (
						<Button
							variant="secondary-outline"
							size="sm"
							onClick={() => onRemove(article)}
							aria-label={`Remove ${article.title_jp} from this catalogue`}
						>
							Remove
						</Button>
					) : null}
				</li>
			))}
		</ul>
	);
};

export default CatalogueArticleList;

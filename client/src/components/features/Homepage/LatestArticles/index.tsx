import * as React from 'react';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import type { ArticleResource } from '@/api/generated/model';
import {
	CompactListLink,
	CompactListRow,
	CompactListSection,
	type CompactListStatus,
} from '@/components/features/Homepage/CompactList';
import { JlptBar } from '@/components/shared/JlptBar';
import { formatDate } from '@/helpers/date';
import styles from './LatestArticles.module.css';

export const LATEST_ARTICLES_FILTERS = { per_page: 4, include_facets: false } as const;

export interface LatestArticlesViewProps {
	status: CompactListStatus;
	articles: ArticleResource[];
	total?: number;
	onRetry: () => void;
	className?: string;
}

export const LatestArticlesView: React.FC<LatestArticlesViewProps> = ({
	status,
	articles,
	total,
	onRetry,
	className,
}) => (
	<CompactListSection
		title="Latest articles"
		allHref="/articles"
		allNoun="articles"
		total={total}
		status={status}
		isEmpty={articles.length === 0}
		emptyText="No articles yet"
		errorText="Couldn't load articles"
		onRetry={onRetry}
		className={className}
	>
		{articles.map((article) => (
			<CompactListRow key={article.uuid}>
				<div className={styles.top}>
					<CompactListLink to={`/articles/${article.uuid}`} className={styles.title} lang="ja">
						{article.title_jp}
					</CompactListLink>
					<time className={styles.date} dateTime={article.created_at}>
						{formatDate(article.created_at)}
					</time>
				</div>
				<JlptBar size="compact" levels={article.jlpt_levels} className={styles.levels} />
			</CompactListRow>
		))}
	</CompactListSection>
);

/** The four newest articles, from the same hook and options the Articles list uses. */
export const LatestArticles: React.FC<{ className?: string }> = ({ className }) => {
	const { articles, total, status, refetch } = useInfiniteArticles({ filters: LATEST_ARTICLES_FILTERS });

	return (
		<LatestArticlesView
			status={status}
			articles={articles}
			total={total}
			onRetry={() => void refetch()}
			className={className}
		/>
	);
};

import React from 'react';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import {
	ProcessingStatus,
	type ProcessingStatus as ProcessingStatusType,
} from '@/api/generated/model/processingStatus';
import { JlptBar } from '@/components/shared/JlptBar';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { processingStatusPill, StatusPill } from '@/components/shared/StatusPill';
import { formatDate } from '@/helpers';
import { CardCover } from '../CardCover';
import { CardDate, CardStats, CardSubtitle, CardTags, CardTitle, LibraryCard, toCount } from '../LibraryCard';
import { articleCoverGlyph, articleCoverLevel } from '../coverRule';

export interface ArticleCardProps {
	article: ArticleResource;
	className?: string;
}

const shouldShowProcessingBadge = (status: string | undefined): status is ProcessingStatusType => {
	if (!status || status === ProcessingStatus.completed) return false;

	return (
		status === ProcessingStatus.pending ||
		status === ProcessingStatus.processing ||
		status === ProcessingStatus.failed
	);
};

/**
 * An article as a Library Card: the cover glyph and dominant level, the date, the Japanese title
 * (two lines at most), the English title, tags, the JLPT bar and the engagement counts.
 */
export const ArticleCard: React.FC<ArticleCardProps> = ({ article, className }) => {
	const status = article.processing_status?.status;
	const level = articleCoverLevel(article.jlpt_levels);
	const stats = article.engagement?.stats;

	return (
		<LibraryCard
			className={className}
			cover={
				<CardCover
					glyph={articleCoverGlyph(article.title_jp)}
					// The JLPT bar already names the dominant level, so the badge stays visual.
					badge={level && <LevelBadge level={level} size="sm" aria-hidden="true" />}
					status={
						shouldShowProcessingBadge(status) && (
							// TODO: should show estimated delivery time on popover click when backend will support estimation
							<StatusPill {...processingStatusPill(status)} />
						)
					}
				/>
			}
		>
			<CardDate dateTime={article.created_at}>{formatDate(article.created_at, 'ja')}</CardDate>
			<CardTitle to={`/articles/${article.uuid}`} lang="ja">
				{article.title_jp}
			</CardTitle>
			{article.title_en && <CardSubtitle>{article.title_en}</CardSubtitle>}
			<CardTags tags={article.hashtags ?? []} />
			<JlptBar size="compact" levels={article.jlpt_levels} />
			<CardStats
				stats={[
					{ kind: 'views', count: toCount(stats?.views_count) },
					{ kind: 'comments', count: toCount(stats?.comments_count) },
					{ kind: 'likes', count: toCount(stats?.likes_count) },
				]}
			/>
		</LibraryCard>
	);
};

export default ArticleCard;

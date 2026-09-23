import React from 'react';
import classNames from 'classnames';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import {
	ProcessingStatus,
	type ProcessingStatus as ProcessingStatusType,
} from '@/api/generated/model/processingStatus';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import ProcessingStatusBadge from '@/components/features/ProcessingStatusAlert/ProcessingStatusBadge';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { Link } from '@/components/shared/Link';
import { formatDate } from '@/helpers';
import styles from './ArticleCard.module.css';

export interface ArticleCardProps {
	article: ArticleResource;
	className?: string;
}

const JLPT_LEVELS: Array<keyof ArticleResource['jlpt_levels']> = ['n1', 'n2', 'n3', 'n4', 'n5', 'uncommon'];

const shouldShowProcessingBadge = (status: string | undefined): status is ProcessingStatusType => {
	if (!status || status === ProcessingStatus.completed) return false;

	return (
		status === ProcessingStatus.pending ||
		status === ProcessingStatus.processing ||
		status === ProcessingStatus.failed
	);
};

export const ArticleCard: React.FC<ArticleCardProps> = ({ article, className }) => {
	const url = `/articles/${article.uuid}`;
	const status = article.processing_status?.status;

	return (
		<article className={classNames(styles.wrapper, className)}>
			<Link to={url} title={article.title_jp} className={styles.cardLink}>
				<div className={styles.imgWrapper}>
					<img src={DefaultArticleImg} alt={article.title_jp} className={styles.image} />

					{shouldShowProcessingBadge(status) && (
						<div className={styles.statusOverlay}>
							{/* TODO: should show estimated delivery time on popover click when backend will support estimation */}
							<ProcessingStatusBadge status={status} />
						</div>
					)}
				</div>
			</Link>

			<div className={styles.date}>{formatDate(article.created_at, 'ja', true)}</div>

			<Link to={url} title={article.title_jp} className={styles.cardLink}>
				<p className={styles.title} lang="ja">
					{article.title_jp}
				</p>
			</Link>

			{article.hashtags?.length ? (
				<div className={styles.chipList}>
					{article.hashtags.map((tag) => (
						<Chip key={tag.id} readonly title={tag.content}>
							{tag.content}
						</Chip>
					))}
				</div>
			) : null}

			<div className={styles.childrenWrapper}>
				<div className={styles.levelRow}>
					{JLPT_LEVELS.map((level) => (
						<LevelBadge key={level} level={level} count={article.jlpt_levels[level]} size="sm" />
					))}
				</div>

				<div className={styles.metaInfo}>
					<div className={styles.statItem}>
						<Icon size="sm" name="eyeRegular" className={styles.statIcon} />
						<span>{article.engagement?.stats?.views_count ?? 0}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="commentSolid" className={styles.statIcon} />
						<span>{article.engagement?.stats?.comments_count ?? 0}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="thumbsUpSolid" className={styles.statIcon} />
						<span>{article.engagement?.stats?.likes_count ?? 0}</span>
					</div>
				</div>
			</div>
		</article>
	);
};

export default ArticleCard;

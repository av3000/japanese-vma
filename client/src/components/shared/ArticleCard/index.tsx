import React from 'react';
import classNames from 'classnames';
import { Article } from '@/api/articles/articles';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import ProcessingStatusBadge from '@/components/features/ProcessingStatusAlert/ProcessingStatusBadge';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { formatDate } from '@/helpers';
import styles from './ArticleCard.module.scss';

export interface ArticleCardProps {
	article: Article;
	className?: string;
}

const shouldShowProcessingBadge = (status: string | undefined): status is LastOperationStatus => {
	if (!status || status === LastOperationStatus.Completed) return false;

	return (
		status === LastOperationStatus.Pending ||
		status === LastOperationStatus.Processing ||
		status === LastOperationStatus.Failed
	);
};

export const ArticleCard: React.FC<ArticleCardProps> = ({ article, className }) => {
	const url = `/articles/${article.uuid}`;
	const status = article.processing_status?.status;

	return (
		<article className={classNames(styles.wrapper, className)}>
			<Link to={url} title={article.title_jp} className="text-decoration-none">
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

			<Link to={url} title={article.title_jp} className="text-decoration-none">
				<p className={styles.title}>{article.title_jp}</p>
			</Link>

			{article.hashtags?.length ? (
				<div className={classNames(styles.chipList, 'd-flex align-items-center flex-wrap')}>
					{article.hashtags.map((tag) => (
						<Chip className="mr-1" key={tag.id} readonly title={tag.content}>
							{tag.content}
						</Chip>
					))}
				</div>
			) : null}

			<div className={styles.childrenWrapper}>
				<div className="d-flex justify-content-between align-items-center">
					<ruby className="h4 mr-2">
						{article.jlpt_levels.n1}
						<rp>(</rp>
						<rt>
							<strong>N1</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{article.jlpt_levels.n2}
						<rp>(</rp>
						<rt>
							<strong>N2</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{article.jlpt_levels.n3}
						<rp>(</rp>
						<rt>
							<strong>N3</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{article.jlpt_levels.n4}
						<rp>(</rp>
						<rt>
							<strong>N4</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{article.jlpt_levels.n5}
						<rp>(</rp>
						<rt>
							<strong>N5</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{article.jlpt_levels.uncommon}
						<rp>(</rp>
						<rt>
							<strong>NA</strong>
						</rt>
						<rp>)</rp>
					</ruby>
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

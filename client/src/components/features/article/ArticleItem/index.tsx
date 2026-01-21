import React from 'react';
import { Article } from '@/api/articles/articles';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import { Card } from '@/components/shared/Card';
import { Icon } from '@/components/shared/Icon';
import styles from './ArticleItem.module.scss';

const ArticleItem: React.FC<Article> = ({
	// id,
	uuid,
	created_at,
	title_jp,
	engagement: { stats },
	hashtags,
	jlpt_levels,
	processing_status,
}) => {
	const currentStatus = processing_status?.status || 'completed';

	useArticleSubscription(currentStatus === 'completed' ? undefined : uuid);

	return (
		<div className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
			<Card
				title={title_jp}
				image={{ url: DefaultArticleImg, title: title_jp, alt: title_jp }}
				url={`/articles/${uuid}`}
				date={created_at} // TODO: use primary date and create date transformations pipes on frontend
				tags={hashtags}
			>
				<div className="d-flex justify-content-between align-items-center">
					<ruby className="h4 mr-2">
						{jlpt_levels.n1}
						<rp>(</rp>
						<rt>
							<strong>N1</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{jlpt_levels.n2}
						<rp>(</rp>
						<rt>
							<strong>N2</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{jlpt_levels.n3}
						<rp>(</rp>
						<rt>
							<strong>N3</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{jlpt_levels.n4}
						<rp>(</rp>
						<rt>
							<strong>N4</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{jlpt_levels.n5}
						<rp>(</rp>
						<rt>
							<strong>N5</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{jlpt_levels.uncommon}
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
						<span>{stats.views_count}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="commentSolid" className={styles.statIcon} />
						<span>{stats.comments_count}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="thumbsUpSolid" className={styles.statIcon} />
						<span>{stats.likes_count}</span>
					</div>
				</div>
			</Card>
		</div>
	);
};

export default ArticleItem;

import React from 'react';
import DefaultArticleImg from '@/assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import { Card } from '@/components/shared/Card';
import { Icon } from '@/components/shared/Icon';
import styles from './ArticleItem.module.scss';

interface Hashtag {
	id: string;
	content: string;
}

interface ArticleItemProps {
	id: string | number;
	created_at: string;
	title_jp: string;
	commentsTotal: number;
	viewsTotal: number;
	likesTotal: number;
	hashtags: Hashtag[];
	n1: number;
	n2: number;
	n3: number;
	n4: number;
	n5: number;
	uncommon: number;
}

const ArticleItem: React.FC<ArticleItemProps> = ({
	id,
	created_at,
	title_jp,
	commentsTotal,
	viewsTotal,
	likesTotal,
	hashtags,
	n1,
	n2,
	n3,
	n4,
	n5,
	uncommon,
}) => {
	return (
		<div className="col-lg-4 col-md-6 col-sm-8 mb-4">
			<Card
				title={title_jp}
				image={{ url: DefaultArticleImg, title: title_jp, alt: title_jp }}
				url={`/article/${id}`}
				date={created_at} // TODO: use primary date and create date transformations on frontend
				tags={hashtags}
			>
				<div className="d-flex justify-content-between align-items-center">
					<ruby className="h4 mr-2">
						{n1}
						<rp>(</rp>
						<rt>
							<strong>N1</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{n2}
						<rp>(</rp>
						<rt>
							<strong>N2</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{n3}
						<rp>(</rp>
						<rt>
							<strong>N3</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{n4}
						<rp>(</rp>
						<rt>
							<strong>N4</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{n5}
						<rp>(</rp>
						<rt>
							<strong>N5</strong>
						</rt>
						<rp>)</rp>
					</ruby>
					<ruby className="h4 mr-2">
						{uncommon}
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
						<span>{viewsTotal}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="commentSolid" className={styles.statIcon} />
						<span>{commentsTotal}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="thumbsUpSolid" className={styles.statIcon} />
						<span>{likesTotal}</span>
					</div>
				</div>
			</Card>
		</div>
	);
};

export default ArticleItem;

import React from 'react';
import DefaultListImg from '@/assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg';
import { Card } from '@/components/shared/Card';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import styles from '../../article/ArticleItem/ArticleItem.module.scss';

// You'll need to create this file

interface Hashtag {
	id: string;
	content: string;
}

interface ListItemProps {
	id: string | number;
	created_at: string;
	title: string;
	listType: string;
	type: number;
	commentsTotal: number;
	itemsTotal: number;
	viewsTotal: number;
	likesTotal: number;
	downloadsTotal: number;
	hashtags: Hashtag[];
	n1?: number;
	n2?: number;
	n3?: number;
	n4?: number;
	n5?: number;
	uncommon?: number;
}

const SavedListItem: React.FC<ListItemProps> = ({
	id,
	created_at,
	title,
	listType,
	type,
	commentsTotal,
	itemsTotal,
	viewsTotal,
	likesTotal,
	downloadsTotal,
	hashtags,
	n1 = 0,
	n2 = 0,
	n3 = 0,
	n4 = 0,
	n5 = 0,
	uncommon = 0,
}) => {
	// TODO: create const enum to clarify magical numbers;
	const isJlptList = type === 2 || type === 6;

	return (
		<div className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
			<Card
				title={title}
				image={{ url: DefaultListImg, title: title, alt: title }}
				url={`/list/${id}`}
				date={created_at}
				tags={hashtags}
			>
				<div className="mb-4">
					<Chip readonly variant="outline">
						{listType}
					</Chip>
				</div>

				{isJlptList && (
					<div className="d-flex justify-content-between align-items-center mb-2">
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
				)}

				<div className={styles.metaInfo}>
					<div className={styles.statItem} title="Items in the list">
						<Icon size="sm" name="layerGroupSolid" className={styles.statIcon} />
						<span>{itemsTotal}</span>
					</div>
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
					<div className={styles.statItem}>
						<Icon size="sm" name="downloadSolid" className={styles.statIcon} />
						<span>{downloadsTotal}</span>
					</div>
				</div>
			</Card>
		</div>
	);
};

export default SavedListItem;

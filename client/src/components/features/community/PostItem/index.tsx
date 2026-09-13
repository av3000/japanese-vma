import React from 'react';
import { Chip } from '@/components/shared/Chip';
import { Link } from '@/components/shared/Link';
import { Cluster } from '@/components/shared/layout';
import styles from './PostItem.module.css';

interface Hashtag {
	id: string | number;
	content: string;
}

interface PostItemProps {
	/** Canonical Post UUID used for detail navigation. */
	detailIdentifier: string;
	title: string;
	commentsTotal: number;
	likesTotal: number;
	viewsTotal: number;
	hashtags: Hashtag[];
	userName: string;
	postType: string;
	date: string;
	isLocked: boolean;
}

const PostItem: React.FC<PostItemProps> = ({
	detailIdentifier,
	title,
	commentsTotal,
	likesTotal,
	viewsTotal,
	hashtags,
	userName,
	postType,
	date,
	isLocked,
}) => {
	return (
		<li className={styles.row}>
			<div>
				<p className={styles.byline}>
					<strong className={styles.strongText}>{userName}</strong>
				</p>
				<h5 className={styles.title}>
					<Link to={`/community/${detailIdentifier}`}>{title}</Link>
				</h5>
				<p className={styles.meta}>Date: {date}</p>
				<Cluster gap="2xs">
					<span>Tags:</span>
					{hashtags.map((tag) => (
						<Chip readonly key={tag.id + tag.content} title={tag.content} name={tag.content}>
							{tag.content}
						</Chip>
					))}
				</Cluster>
			</div>
			<small className={styles.stats}>
				<strong className={styles.strongText}>{postType}</strong>
				<span>{commentsTotal}&nbsp;Comments</span>
				<span>{viewsTotal}&nbsp;Views</span>
				<span>{likesTotal}&nbsp;Likes</span>
				{isLocked && <strong>Locked</strong>}
			</small>
		</li>
	);
};

export default PostItem;

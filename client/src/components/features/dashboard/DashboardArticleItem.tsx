import React from 'react';
import type { HashtagResource } from '@/api/generated/model';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Cluster } from '@/components/shared/layout';
import ArticleStatus from '../../ui/article-status';
import styles from './DashboardRow.module.css';

interface DashboardArticleItemProps {
	uuid: string;
	created_at: string;
	title_jp: string;
	status: number;
	commentsTotal: number;
	likesTotal: number;
	viewsTotal: number;
	hashtags?: HashtagResource[];
}

const DashboardArticleItem: React.FC<DashboardArticleItemProps> = ({
	uuid,
	created_at,
	title_jp,
	status,
	commentsTotal,
	likesTotal,
	viewsTotal,
	hashtags = [],
}) => (
	<li className={styles.row}>
		<div>
			<p className={styles.title}>{title_jp}</p>
			<Cluster gap="xs">
				<span className={styles.label}>Tags:</span>
				<Cluster as="span" gap="3xs">
					{hashtags.map((tag) => (
						<Chip readonly key={tag.id + tag.content} title={tag.content} name={tag.content}>
							{tag.content}
						</Chip>
					))}
				</Cluster>
				<span className={styles.label}>Status:</span>
				<ArticleStatus status={status} />
			</Cluster>
		</div>
		<div>
			<Cluster justify="between" gap="xs" className={styles.stats}>
				<span>{commentsTotal} Comments</span>
				<span>{viewsTotal} Views</span>
				<span>{likesTotal} Likes</span>
				<Button to={`/articles/${uuid}`} variant="outline" size="sm" type="button" aria-label="Open article">
					<Icon name="externalLink" size="sm" />
				</Button>
			</Cluster>
			<small className={styles.meta}>{created_at}</small>
		</div>
	</li>
);

export default DashboardArticleItem;

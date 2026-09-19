import React from 'react';
import type { Catalogue } from '@/api/catalogues/catalogues';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Cluster } from '@/components/shared/layout';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import styles from './DashboardRow.module.css';

const DashboardListItem: React.FC<Catalogue> = ({ uuid, hashtags, title, engagement, type_label, created_at }) => (
	<li className={styles.row}>
		<div>
			<p className={styles.title}>{title}</p>
			<Cluster gap="xs">
				<span className={styles.label}>Tags:</span>
				<Cluster as="span" gap="3xs">
					{hashtags.map((tag) => (
						<Chip readonly key={tag.id + tag.content} title={tag.content} name={tag.content}>
							{tag.content}
						</Chip>
					))}
				</Cluster>
			</Cluster>
		</div>
		<div>
			<Cluster justify="between" gap="xs" className={styles.stats}>
				<span>{engagement?.comments_count} Comments</span>
				<span>{engagement?.views_count} Views</span>
				<span>{engagement?.likes_count} Likes</span>
				<Button
					to={CATALOGUE_ROUTES.detail(uuid)}
					variant="ghost"
					size="sm"
					type="button"
					aria-label="Open list"
				>
					<Icon name="externalLink" size="sm" />
				</Button>
			</Cluster>
			<small className={styles.meta}>ListType: {type_label}</small>
			<small className={styles.meta}>{created_at}</small>
		</div>
	</li>
);

export default DashboardListItem;

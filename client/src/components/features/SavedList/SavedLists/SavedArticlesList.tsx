import React from 'react';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { User } from '@/types';
import sharedStyles from '../SharedListStyles.module.scss';

interface SavedArticlesListProps {
	objects: CatalogueArticleItem[];
	removeFromList: (id: string | number) => void;
	currentUser: User;
	listUserId: string | number;
}

const SavedArticlesList: React.FC<SavedArticlesListProps> = ({ objects, removeFromList, currentUser, listUserId }) => {
	return (
		<div className={sharedStyles.listContainer}>
			{objects.map((article) => {
				return (
					<div key={article.id} className={sharedStyles.itemCard}>
						<div className={sharedStyles.itemHeader}>
							<h5 className={sharedStyles.articleTitle}>
								<Link to={`/articles/${article.uuid}`} target="_blank">
									{article.title_jp}
									<Icon size="sm" name="externalLink" />
								</Link>
							</h5>
							{currentUser?.id === listUserId && (
								<Button
									type="button"
									size="md"
									hasOnlyIcon
									variant="danger"
									onClick={() => removeFromList(article.id)}
									className={sharedStyles.removeButton}
								>
									<Icon size="sm" name="minusSolid" />
								</Button>
							)}
						</div>

						<div className={sharedStyles.itemDetails}>
							<section className="mt-2 d-flex align-items-center flex-wrap">
								{article.hashtags.map((tag) => (
									<Chip
										className="mr-1"
										readonly
										key={tag.id + tag.content}
										title={tag.content}
										name={tag.content}
									>
										{tag.content}
									</Chip>
								))}
							</section>
						</div>

						<div className={sharedStyles.metaInfo}>
							<div className={sharedStyles.statItem}>
								<Icon size="sm" name="eyeRegular" className={sharedStyles.statIcon} />
								<span>{article.engagement?.views_count ?? 0}</span>
							</div>
							<div className={sharedStyles.statItem}>
								<Icon size="sm" name="bookmarkRegular" className={sharedStyles.statIcon} />
								<span>{article.saves_count}</span>
							</div>
							<div className={sharedStyles.statItem}>
								<Icon size="sm" name="downloadSolid" className={sharedStyles.statIcon} />
								<span>{article.engagement?.downloads_count ?? 0}</span>
							</div>
							<div className={sharedStyles.statItem}>
								<Icon size="sm" name="commentSolid" className={sharedStyles.statIcon} />
								<span>{article.engagement?.comments_count ?? 0}</span>
							</div>
							<div className={sharedStyles.statItem}>
								<Icon size="sm" name="thumbsUpSolid" className={sharedStyles.statIcon} />
								<span>{article.engagement?.likes_count ?? 0}</span>
							</div>
						</div>
					</div>
				);
			})}

			{objects.length === 0 && (
				<div className={sharedStyles.emptyState}>
					<p>No saved articles found.</p>
				</div>
			)}
		</div>
	);
};

export default SavedArticlesList;

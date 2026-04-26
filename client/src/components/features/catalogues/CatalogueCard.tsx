import DefaultListImg from '@/assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg';
import { Card } from '@/components/shared/Card';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { CATALOGUE_ROUTES, resolveCatalogueTypeLabel } from '@/shared/constants/catalogues';
import type { Catalogue } from '@/api/catalogues/catalogues';
import styles from '@/components/shared/ArticleCard/ArticleCard.module.scss';

interface CatalogueCardProps {
	catalogue: Catalogue;
}

export const CatalogueCard = ({ catalogue }: CatalogueCardProps) => {
	const typeLabel = catalogue.type_label || resolveCatalogueTypeLabel(catalogue.type);
	const likes = catalogue.engagement?.likes_count ?? 0;
	const views = catalogue.engagement?.views_count ?? 0;
	const comments = catalogue.engagement?.comments_count ?? 0;
	const downloads = catalogue.engagement?.downloads_count ?? 0;

	return (
		<div className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
			<Card
				title={catalogue.title}
				image={{ url: DefaultListImg, title: catalogue.title, alt: catalogue.title }}
				url={CATALOGUE_ROUTES.detail(catalogue.uuid)}
				date={catalogue.created_at}
				tags={catalogue.hashtags.map((tag) => ({
					...tag,
					id: String(tag.id),
				}))}
			>
				<div className="mb-4">
					<Chip readonly variant="outline">
						{typeLabel}
					</Chip>
				</div>

				<div className={styles.metaInfo}>
					<div className={styles.statItem} title="Items in the catalogue">
						<Icon size="sm" name="layerGroupSolid" className={styles.statIcon} />
						<span>{catalogue.items_count}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="eyeRegular" className={styles.statIcon} />
						<span>{views}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="commentSolid" className={styles.statIcon} />
						<span>{comments}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="thumbsUpSolid" className={styles.statIcon} />
						<span>{likes}</span>
					</div>
					<div className={styles.statItem}>
						<Icon size="sm" name="downloadSolid" className={styles.statIcon} />
						<span>{downloads}</span>
					</div>
				</div>
			</Card>
		</div>
	);
};

import classNames from 'classnames';
import cardStyles from '@/components/shared/Card/Card.module.scss';
import skeletonStyles from './CatalogueCardSkeleton.module.scss';

const CATALOGUE_META_PLACEHOLDERS = ['items', 'views', 'comments', 'likes', 'downloads'];

export const CatalogueCardSkeleton = () => (
	<div aria-hidden="true" className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4" data-testid="catalogue-card-skeleton">
		<article className={classNames(cardStyles.wrapper, skeletonStyles.wrapper)}>
			<div>
				<div className={classNames(cardStyles.imgWrapper, skeletonStyles.block, skeletonStyles.image)} />
				<span className={classNames(cardStyles.date, skeletonStyles.block, skeletonStyles.line, skeletonStyles.date)} />
				<span className={classNames(cardStyles.title, skeletonStyles.block, skeletonStyles.line, skeletonStyles.title)} />
				<div className={cardStyles.chipList}>
					<span className={classNames(skeletonStyles.block, skeletonStyles.chip)} />
				</div>
			</div>

			<div className={cardStyles.childrenWrapper}>
				<div className={skeletonStyles.metaRow}>
					{CATALOGUE_META_PLACEHOLDERS.map((item) => (
						<span key={item} className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.metaItem)} />
					))}
				</div>
			</div>
		</article>
	</div>
);

export default CatalogueCardSkeleton;

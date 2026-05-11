import classNames from 'classnames';
import skeletonStyles from './CatalogueCardSkeleton.module.scss';

const CATALOGUE_META_PLACEHOLDERS = ['items', 'views', 'comments', 'likes', 'downloads'];

export const CatalogueCardSkeleton = () => (
	<div aria-hidden="true" className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4" data-testid="catalogue-card-skeleton">
		<article className={skeletonStyles.wrapper}>
			<div className={skeletonStyles.content}>
				<div
					className={classNames(skeletonStyles.block, skeletonStyles.image)}
					data-testid="catalogue-card-skeleton-image"
				/>
				<span
					className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.date)}
					data-testid="catalogue-card-skeleton-date"
				/>
				<span
					className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.title)}
					data-testid="catalogue-card-skeleton-title"
				/>
				<div className={skeletonStyles.chipList}>
					<span
						className={classNames(skeletonStyles.block, skeletonStyles.chip)}
						data-testid="catalogue-card-skeleton-chip"
					/>
				</div>
			</div>

			<div className={skeletonStyles.children}>
				<div className={skeletonStyles.metaRow}>
					{CATALOGUE_META_PLACEHOLDERS.map((item) => (
						<span
							key={item}
							className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.metaItem)}
							data-testid="catalogue-card-skeleton-stat"
						/>
					))}
				</div>
			</div>
		</article>
	</div>
);

export default CatalogueCardSkeleton;

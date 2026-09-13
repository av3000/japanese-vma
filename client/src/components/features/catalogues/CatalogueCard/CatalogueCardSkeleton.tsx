import classNames from 'classnames';
import skeletonStyles from './CatalogueCardSkeleton.module.scss';

const CATALOGUE_META_PLACEHOLDERS = ['items', 'views', 'comments', 'likes', 'downloads'];

export const CatalogueCardSkeleton = () => (
	<article className={skeletonStyles.wrapper} aria-hidden="true" data-testid="catalogue-card-skeleton">
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
);

export default CatalogueCardSkeleton;

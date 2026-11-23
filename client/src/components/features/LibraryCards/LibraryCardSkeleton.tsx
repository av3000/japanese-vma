import classNames from 'classnames';
import styles from './LibraryCard.module.css';
import skeletonStyles from './LibraryCardSkeleton.module.css';

const ROWS = {
	article: { testId: 'article-card-skeleton', date: true, subtitle: true, owner: false, bar: true, stats: 3 },
	catalogue: { testId: 'catalogue-card-skeleton', date: false, subtitle: true, owner: true, bar: false, stats: 4 },
} as const;

export type LibraryCardSkeletonKind = keyof typeof ROWS;

const Line = ({ className, testId }: { className: string; testId: string }) => (
	<span className={classNames(skeletonStyles.block, skeletonStyles.line, className)} data-testid={testId} />
);

/**
 * A Library Card while it loads, row for row: the same surface, cover footprint and body order as
 * `ArticleCard` / `CatalogueCard`, so the grid does not jump when the data arrives.
 */
export const LibraryCardSkeleton = ({ kind }: { kind: LibraryCardSkeletonKind }) => {
	const rows = ROWS[kind];
	const id = rows.testId;

	return (
		<article aria-hidden="true" className={classNames(styles.card, skeletonStyles.card)} data-testid={id}>
			<div className={classNames(skeletonStyles.block, skeletonStyles.cover)} data-testid={`${id}-cover`} />

			<div className={styles.body}>
				{rows.date && <Line className={skeletonStyles.date} testId={`${id}-date`} />}
				<Line className={skeletonStyles.title} testId={`${id}-title`} />
				{rows.subtitle && <Line className={skeletonStyles.subtitle} testId={`${id}-subtitle`} />}
				{rows.owner && <Line className={skeletonStyles.date} testId={`${id}-owner`} />}

				<div className={skeletonStyles.tags}>
					{[0, 1].map((index) => (
						<span
							key={index}
							className={classNames(skeletonStyles.block, skeletonStyles.tag)}
							data-testid={`${id}-pill`}
						/>
					))}
				</div>

				{rows.bar && <Line className={skeletonStyles.bar} testId={`${id}-jlpt-bar`} />}

				<div className={skeletonStyles.stats}>
					{Array.from({ length: rows.stats }, (_, index) => (
						<Line key={index} className={skeletonStyles.stat} testId={`${id}-stat`} />
					))}
				</div>
			</div>
		</article>
	);
};

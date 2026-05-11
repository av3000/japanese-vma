import classNames from 'classnames';
import skeletonStyles from './ArticleCardSkeleton.module.scss';

const ARTICLE_LEVEL_PLACEHOLDERS = ['n1', 'n2', 'n3', 'n4', 'n5', 'na'];
const ARTICLE_META_PLACEHOLDERS = ['views', 'comments', 'likes'];

export const ArticleCardSkeleton = () => (
	<article
		aria-hidden="true"
		className={skeletonStyles.wrapper}
		data-testid="article-card-skeleton"
	>
		<div
			className={classNames(skeletonStyles.block, skeletonStyles.image)}
			data-testid="article-card-skeleton-image"
		/>

		<span
			className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.date)}
			data-testid="article-card-skeleton-date"
		/>
		<span
			className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.title)}
			data-testid="article-card-skeleton-title"
		/>

		<div className={skeletonStyles.chipList}>
			<span
				className={classNames(skeletonStyles.block, skeletonStyles.pill)}
				data-testid="article-card-skeleton-pill"
			/>
			<span
				className={classNames(skeletonStyles.block, skeletonStyles.pill)}
				data-testid="article-card-skeleton-pill"
			/>
		</div>

		<div className={skeletonStyles.children}>
			<div className={skeletonStyles.levelRow}>
				{ARTICLE_LEVEL_PLACEHOLDERS.map((level) => (
					<span
						key={level}
						className={classNames(skeletonStyles.block, skeletonStyles.level)}
						data-testid="article-card-skeleton-level"
					/>
				))}
			</div>

			<div className={skeletonStyles.metaRow}>
				{ARTICLE_META_PLACEHOLDERS.map((item) => (
					<span
						key={item}
						className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.metaItem)}
						data-testid="article-card-skeleton-stat"
					/>
				))}
			</div>
		</div>
	</article>
);

export default ArticleCardSkeleton;

import classNames from 'classnames';
import styles from './ArticleCard.module.scss';
import skeletonStyles from './ArticleCardSkeleton.module.scss';

const ARTICLE_LEVEL_PLACEHOLDERS = ['n1', 'n2', 'n3', 'n4', 'n5', 'na'];
const ARTICLE_META_PLACEHOLDERS = ['views', 'comments', 'likes'];

export const ArticleCardSkeleton = () => (
	<article
		aria-hidden="true"
		className={classNames(styles.wrapper, skeletonStyles.wrapper)}
		data-testid="article-card-skeleton"
	>
		<div className={classNames(styles.imgWrapper, skeletonStyles.block, skeletonStyles.image)} />

		<span className={classNames(styles.date, skeletonStyles.block, skeletonStyles.line, skeletonStyles.date)} />
		<span className={classNames(styles.title, skeletonStyles.block, skeletonStyles.line, skeletonStyles.title)} />

		<div className={styles.chipList}>
			<span className={classNames(skeletonStyles.block, skeletonStyles.pill)} />
			<span className={classNames(skeletonStyles.block, skeletonStyles.pill)} />
		</div>

		<div className={styles.childrenWrapper}>
			<div className="d-flex justify-content-between align-items-center">
				{ARTICLE_LEVEL_PLACEHOLDERS.map((level) => (
					<span key={level} className={classNames(skeletonStyles.block, skeletonStyles.level)} />
				))}
			</div>

			<div className={styles.metaInfo}>
				{ARTICLE_META_PLACEHOLDERS.map((item) => (
					<span key={item} className={classNames(skeletonStyles.block, skeletonStyles.line, skeletonStyles.metaItem)} />
				))}
			</div>
		</div>
	</article>
);

export default ArticleCardSkeleton;

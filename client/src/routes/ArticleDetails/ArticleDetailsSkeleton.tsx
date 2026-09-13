import classNames from 'classnames';
import skeletonStyles from '@/components/shared/ArticleCard/ArticleCardSkeleton.module.css';
import { Container } from '@/components/shared/layout';
import styles from './ArticleDetailsSkeleton.module.css';

const ARTICLE_PARAGRAPH_LINES = [
	styles.paragraphLineFull,
	styles.paragraphLineFull,
	styles.paragraphLineMedium,
	styles.paragraphLineShort,
];

const ARTICLE_CHIPS = ['first', 'second', 'third'];

const ArticleDetailsSkeleton = () => (
	<Container size="sm" className={styles.page} data-testid="article-details-skeleton" aria-hidden="true">
		<span className={classNames(styles.backLink, skeletonStyles.block, skeletonStyles.line)} />

		<span className={classNames(styles.title, skeletonStyles.block, skeletonStyles.line)} />

		<div className={styles.metaRow}>
			<div className={styles.metaBlock}>
				<span
					className={classNames(
						styles.metaLine,
						styles.metaLineLong,
						skeletonStyles.block,
						skeletonStyles.line,
					)}
				/>
				<span
					className={classNames(
						styles.metaLine,
						styles.metaLineShort,
						skeletonStyles.block,
						skeletonStyles.line,
					)}
				/>
			</div>

			<div className={styles.statusCluster}>
				<span className={classNames(styles.statusPill, skeletonStyles.block)} />
				<span className={classNames(styles.statusPill, skeletonStyles.block)} />
			</div>
		</div>

		<div className={classNames(styles.cover, skeletonStyles.block)} data-testid="article-details-skeleton-cover" />

		<div className={styles.paragraphStack}>
			{ARTICLE_PARAGRAPH_LINES.map((widthClassName, index) => (
				<span
					key={index}
					className={classNames(
						styles.paragraphLine,
						widthClassName,
						skeletonStyles.block,
						skeletonStyles.line,
					)}
					data-testid="article-details-skeleton-paragraph"
				/>
			))}
		</div>

		<div className={styles.chipList}>
			{ARTICLE_CHIPS.map((chip) => (
				<span
					key={chip}
					className={classNames(styles.chip, skeletonStyles.block)}
					data-testid="article-details-skeleton-chip"
				/>
			))}
		</div>

		<hr className={styles.divider} />

		<div className={styles.authorRow}>
			<div className={styles.author} data-testid="article-details-skeleton-author">
				<span className={classNames(styles.avatar, skeletonStyles.block)} />
				<span className={classNames(styles.authorLine, skeletonStyles.block, skeletonStyles.line)} />
			</div>

			<div className={styles.actionCluster}>
				<span className={classNames(styles.action, skeletonStyles.block)} />
				<span className={classNames(styles.action, skeletonStyles.block)} />
				<span className={classNames(styles.action, skeletonStyles.block)} />
			</div>
		</div>

		<div className={styles.comments} data-testid="article-details-skeleton-comments">
			<span className={classNames(styles.commentsTitle, skeletonStyles.block, skeletonStyles.line)} />
			<span
				className={classNames(
					styles.commentLine,
					styles.paragraphLineFull,
					skeletonStyles.block,
					skeletonStyles.line,
				)}
			/>
			<span
				className={classNames(
					styles.commentLine,
					styles.paragraphLineMedium,
					skeletonStyles.block,
					skeletonStyles.line,
				)}
			/>
		</div>
	</Container>
);

export default ArticleDetailsSkeleton;

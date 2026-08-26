import type { ReactNode } from 'react';
import styles from './PageLoading.module.scss';

export const PAGE_LOADING_FAMILIES = ['list', 'detail', 'form', 'dashboard', 'generic'] as const;
export type PageLoadingFamily = (typeof PAGE_LOADING_FAMILIES)[number];

type PageLoadingProps = {
	readonly family?: PageLoadingFamily;
	readonly visual?: ReactNode;
	readonly label?: string;
};

const Block = ({ className = '' }: { readonly className?: string }) => (
	<span className={`${styles.block} ${className}`.trim()} />
);

const ListVisual = () => (
	<div className={styles.list} aria-hidden="true">
		<div className={styles.controls}>
			<Block />
			<Block />
			<Block />
		</div>
		<Block className={styles.summary} />
		<div className={styles.cards}>
			{Array.from({ length: 6 }, (_, index) => (
				<Block key={index} className={styles.card} />
			))}
		</div>
	</div>
);

const DetailVisual = () => (
	<div className={styles.detail} aria-hidden="true">
		<Block className={styles.back} />
		<Block className={styles.title} />
		<Block className={styles.meta} />
		<Block className={styles.hero} />
		<Block />
		<Block />
		<Block className={styles.shortLine} />
	</div>
);

const FormVisual = () => (
	<div className={styles.form} aria-hidden="true">
		<Block className={styles.title} />
		{Array.from({ length: 3 }, (_, index) => (
			<Block key={index} className={styles.field} />
		))}
		<Block className={styles.submit} />
	</div>
);

const DashboardVisual = () => (
	<div className={styles.dashboard} aria-hidden="true">
		<div className={styles.controls}>
			<Block />
			<Block />
		</div>
		<Block className={styles.panel} />
	</div>
);

const GenericVisual = () => (
	<div className={styles.generic} aria-hidden="true">
		<Block className={styles.title} />
		<Block />
		<Block className={styles.shortLine} />
	</div>
);

const FAMILY_VISUALS: Record<PageLoadingFamily, ReactNode> = {
	list: <ListVisual />,
	detail: <DetailVisual />,
	form: <FormVisual />,
	dashboard: <DashboardVisual />,
	generic: <GenericVisual />,
};

const PageLoading = ({ family = 'generic', visual, label = 'Loading page.' }: PageLoadingProps) => (
	<section className={styles.region} aria-busy="true" data-loading-family={family}>
		<span className={styles.status} role="status">
			{label}
		</span>
		{visual ?? FAMILY_VISUALS[family]}
	</section>
);

export default PageLoading;

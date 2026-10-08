import * as React from 'react';
import classNames from 'classnames';
import styles from './DetailLayout.module.css';

export interface DetailLayoutProps {
	/** Back link, title, byline and meta. Spans the reading column. */
	header?: React.ReactNode;
	/** The reading column, at most 720px wide. */
	main: React.ReactNode;
	/** Rail, top: the "In this …" facts card. */
	facts?: React.ReactNode;
	/** Rail, middle: like, save, download, owner and moderator actions. Below 1024px, right under `header`. */
	actions?: React.ReactNode;
	/** Rail, bottom: tags, source and similar extras. */
	extra?: React.ReactNode;
	/** Full width under both columns, e.g. related lists and comments. */
	after?: React.ReactNode;
	/** Accessible name of the rail, e.g. "About this article". */
	railLabel: string;
	/**
	 * `reading` (default): a 720px column with the rail beside it, for text.
	 * `wide`: for list-like pages whose `main` is a table. From 1024px the rail becomes one band
	 * under the header (facts, actions and extras side by side) and `main` takes the full width.
	 */
	variant?: 'reading' | 'wide';
	className?: string;
}

/** Space kept between the sticky rail and the top and bottom of the viewport. */
const STICKY_MARGIN = 48;

/**
 * True while the element fits in the viewport, so the rail is only made sticky when it can be read
 * in full. A sticky rail taller than the screen would keep its lower half out of reach.
 */
const useFitsViewport = (ref: React.RefObject<HTMLElement | null>) => {
	const [fits, setFits] = React.useState(false);

	React.useEffect(() => {
		const element = ref.current;

		if (!element || typeof window === 'undefined') return;

		const measure = () => setFits(element.offsetHeight + STICKY_MARGIN <= window.innerHeight);

		measure();
		window.addEventListener('resize', measure);
		const observer = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(measure);
		observer?.observe(element);

		return () => {
			window.removeEventListener('resize', measure);
			observer?.disconnect();
		};
	}, [ref]);

	return fits;
};

/**
 * The Reading Room shell for detail pages: a 720px reading column with a 280px rail beside it from
 * 1024px. Below 1024px everything stacks in one column in this order: header, actions, main, facts,
 * extra, after. The rail's children are then placed by grid areas rather than rendered a second
 * time, so every action, dialog and request exists once whatever the width.
 */
export const DetailLayout: React.FC<DetailLayoutProps> = ({
	header,
	main,
	facts,
	actions,
	extra,
	after,
	railLabel,
	variant = 'reading',
	className,
}) => {
	const railRef = React.useRef<HTMLElement | null>(null);
	const railFits = useFitsViewport(railRef);
	const hasRail = Boolean(facts || actions || extra);

	return (
		<div className={classNames(styles.layout, variant === 'wide' && styles.wide, className)}>
			{header ? <div className={styles.header}>{header}</div> : null}
			<div className={styles.main}>{main}</div>
			{hasRail ? (
				<aside
					ref={railRef}
					className={styles.rail}
					aria-label={railLabel}
					data-sticky={variant === 'reading' && railFits ? 'true' : undefined}
				>
					{facts ? <div className={styles.facts}>{facts}</div> : null}
					{actions ? <div className={styles.actions}>{actions}</div> : null}
					{extra ? <div className={styles.extra}>{extra}</div> : null}
				</aside>
			) : null}
			{after ? <div className={styles.after}>{after}</div> : null}
		</div>
	);
};

export default DetailLayout;

export { DetailUnavailable, unavailableMessage } from './DetailUnavailable';

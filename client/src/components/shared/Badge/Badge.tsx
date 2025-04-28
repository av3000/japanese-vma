// Badge.tsx
import React, { useState, useEffect } from 'react';
import styles from './Badge.module.scss';
import { BaseBadgeProps } from './types';

export interface BadgeProps extends BaseBadgeProps {
	/**
	 * If true, the badge will animate when content changes
	 * @default false
	 */
	animated?: boolean;
}

/**
 * Badge component that displays a small badge to the corner of its child
 */
export const Badge: React.FC<BadgeProps> = ({
	anchorOrigin = { vertical: 'top', horizontal: 'right' },
	children,
	badgeContent,
	color = 'primary',
	invisible: invisibleProp = false,
	max,
	showZero = false,
	variant = 'standard',
	className,
	standalone = false,
	animated = false,
	...other
}) => {
	const [doAnimate, setDoAnimate] = useState(false);

	const isInvisible = invisibleProp || (badgeContent === 0 && !showZero);

	let displayValue = badgeContent;
	if (max !== undefined && badgeContent !== undefined && typeof badgeContent === 'number' && badgeContent > max) {
		displayValue = `${max}+`;
	}

	const hasValue =
		variant === 'dot' || (badgeContent !== undefined && (badgeContent !== 0 || showZero) && !invisibleProp);

	// Animation effect when badge content changes
	useEffect(() => {
		// Only animate when animated is true and value is larger than 1
		if (!animated || !badgeContent || (typeof badgeContent === 'number' && badgeContent < 2)) {
			return;
		}

		setDoAnimate(true);
		const timer = setTimeout(() => {
			setDoAnimate(false);
		}, 500);

		return () => clearTimeout(timer);
	}, [badgeContent, animated]);

	const badgeClasses = [
		styles.badge,
		styles[`color-${color}`],
		styles[`anchor-${anchorOrigin.vertical}-${anchorOrigin.horizontal}`],
		styles[variant],
		hasValue ? styles['has-value'] : '',
		doAnimate ? styles['do-animate'] : '',
		standalone ? styles.standalone : '',
		isInvisible ? styles.invisible : '',
		className || '',
	]
		.filter(Boolean)
		.join(' ');

	return (
		<div className={styles.root} {...other}>
			{children}
			<span className={badgeClasses}>{variant !== 'dot' ? displayValue : null}</span>
		</div>
	);
};

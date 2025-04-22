import React from 'react';
import classNames from 'classnames';
import styles from './badge.module.scss';

export type BadgeVariant = 'neutral' | 'danger';

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
	children?: React.ReactNode;
	className?: string;
	isPositioned?: boolean;
	variant?: BadgeVariant;
}

/**
 * Badge component for showing status indicators and numeric values
 */
export const Badge: React.FC<BadgeProps> = ({
	children,
	className,
	isPositioned = false,
	variant = 'danger',
	...restProps
}) => {
	const badgeClassName = classNames(styles.badge, styles[variant], isPositioned && styles.positioned, className);

	return (
		<span className={badgeClassName} {...restProps}>
			{children}
		</span>
	);
};

/**
 * BadgeWrapper component for positioning a badge relative to a child element
 */
export interface BadgeWrapperProps {
	/** The element to wrap with a badge */
	children: React.ReactNode;
	badgeContent?: React.ReactNode;
	className?: string;
	badgeProps?: Omit<BadgeProps, 'children' | 'isPositioned'>;
}

export const BadgeWrapper: React.FC<BadgeWrapperProps> = ({ children, badgeContent, className, badgeProps }) => {
	return (
		<span className={classNames(styles.wrapper, className)}>
			{children}
			<Badge isPositioned {...badgeProps}>
				{badgeContent}
			</Badge>
		</span>
	);
};

export default Badge;

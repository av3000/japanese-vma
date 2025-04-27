import React from 'react';

export type BadgeColorType = 'primary' | 'secondary' | 'error' | 'success' | 'warning';

export interface BaseBadgeProps {
	/**
	 * The anchor of the badge.
	 * @default { vertical: 'top', horizontal: 'right' }
	 */
	anchorOrigin?: {
		vertical: 'top' | 'bottom';
		horizontal: 'left' | 'right';
	};
	/**
	 * The content rendered within the badge.
	 */
	badgeContent?: React.ReactNode;
	/**
	 * The badge will be added relative to this node.
	 * Optional only when used as a standalone badge (like a status indicator)
	 */
	children?: React.ReactNode;
	/**
	 * Override or extend the styles applied to the component.
	 */
	className?: string;
	/**
	 * The color of the component.
	 * @default 'primary'
	 */
	color?: BadgeColorType;
	/**
	 * If `true`, the badge is invisible.
	 * @default false
	 */
	invisible?: boolean;
	/**
	 * Max count to show.
	 */
	max?: number;
	/**
	 * Controls whether the badge is hidden when `badgeContent` is zero.
	 * @default false
	 */
	showZero?: boolean;
	/**
	 * The variant to use.
	 * @default 'standard'
	 */
	variant?: 'standard' | 'dot';
	/**
	 * If true, the badge will be styled as a standalone badge without wrapping content
	 * @default false
	 */
	standalone?: boolean;
}

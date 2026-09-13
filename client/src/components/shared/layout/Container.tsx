import * as React from 'react';
import classNames from 'classnames';
import styles from './layout.module.css';
import type { LayoutBaseProps } from './types';

export type ContainerSize = 'xs' | 'sm' | 'md' | 'lg' | 'fluid';

export interface ContainerProps extends LayoutBaseProps {
	/** Max width token from src/styles/00-settings/sizes. Defaults to `lg`. */
	size?: ContainerSize;
}

const sizeClass: Record<ContainerSize, string> = {
	xs: styles.containerXs,
	sm: styles.containerSm,
	md: styles.containerMd,
	lg: styles.containerLg,
	fluid: styles.containerFluid,
};

/**
 * Centered, width-constrained wrapper. Replaces Bootstrap `.container`.
 */
export const Container: React.FC<ContainerProps> = ({ as = 'div', size = 'lg', className, children, ...rest }) =>
	React.createElement(as, { className: classNames(styles.container, sizeClass[size], className), ...rest }, children);

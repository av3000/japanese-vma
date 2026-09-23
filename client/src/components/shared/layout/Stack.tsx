import * as React from 'react';
import classNames from 'classnames';
import styles from './layout.module.css';
import { alignCss, spacingVar, withVars, type LayoutAlign, type LayoutBaseProps, type SpacingToken } from './types';

export interface StackProps extends LayoutBaseProps {
	/** Vertical gap between children. Defaults to `md`. */
	gap?: SpacingToken;
	/** Cross-axis alignment. Defaults to `stretch`. */
	align?: LayoutAlign;
}

/**
 * Vertical flex column with a consistent gap. Replaces stacked `mt-*`/`mb-*` chains.
 */
export const Stack: React.FC<StackProps> = ({ as = 'div', gap, align, className, style, children, ...rest }) =>
	React.createElement(
		as,
		{
			className: classNames(styles.stack, className),
			style: withVars(style, { '--layout-gap': spacingVar(gap), '--layout-align': alignCss(align) }),
			...rest,
		},
		children,
	);

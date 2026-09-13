import * as React from 'react';
import classNames from 'classnames';
import styles from './layout.module.css';
import {
	alignCss,
	justifyCss,
	spacingVar,
	withVars,
	type LayoutAlign,
	type LayoutBaseProps,
	type LayoutJustify,
	type SpacingToken,
} from './types';

export interface ClusterProps extends LayoutBaseProps {
	/** Gap between items. Defaults to `xs`. */
	gap?: SpacingToken;
	/** Cross-axis alignment. Defaults to `center`. */
	align?: LayoutAlign;
	/** Main-axis distribution. Defaults to `start`. */
	justify?: LayoutJustify;
	/** Allow wrapping. Defaults to `true`. */
	wrap?: boolean;
}

/**
 * Horizontal, wrapping flex row. Replaces `d-flex align-items-center flex-wrap` and friends.
 */
export const Cluster: React.FC<ClusterProps> = ({
	as = 'div',
	gap,
	align,
	justify,
	wrap = true,
	className,
	style,
	children,
	...rest
}) =>
	React.createElement(
		as,
		{
			className: classNames(styles.cluster, !wrap && styles.clusterNoWrap, className),
			style: withVars(style, {
				'--layout-gap': spacingVar(gap),
				'--layout-align': alignCss(align),
				'--layout-justify': justifyCss(justify),
			}),
			...rest,
		},
		children,
	);

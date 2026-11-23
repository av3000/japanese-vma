import * as React from 'react';
import classNames from 'classnames';
import styles from './layout.module.css';
import {
	alignCss,
	responsiveVars,
	spacingVar,
	withVars,
	type LayoutAlign,
	type LayoutBaseProps,
	type Responsive,
	type SpacingToken,
} from './types';

export interface GridProps extends LayoutBaseProps {
	/** Track count, optionally per breakpoint. Defaults to 12. */
	columns?: Responsive<number>;
	/** Gap between tracks. Defaults to `lg`. */
	gap?: SpacingToken;
	/** Cross-axis alignment of items. Defaults to `stretch`. */
	align?: LayoutAlign;
}

export interface GridItemProps extends LayoutBaseProps {
	/**
	 * Columns to span, optionally per breakpoint. Defaults to the full row.
	 * `auto` lets the item take a single auto-placed track.
	 */
	span?: Responsive<number> | 'auto';
}

const GridRoot: React.FC<GridProps> = ({ as = 'div', columns, gap, align, className, style, children, ...rest }) =>
	React.createElement(
		as,
		{
			className: classNames(styles.grid, className),
			style: withVars(style, {
				...responsiveVars('grid-cols', columns),
				'--layout-gap': spacingVar(gap),
				'--layout-align': alignCss(align),
			}),
			...rest,
		},
		children,
	);

const GridItem: React.FC<GridItemProps> = ({ as = 'div', span, className, style, children, ...rest }) =>
	React.createElement(
		as,
		{
			className: classNames(styles.gridItem, span === 'auto' && styles.gridItemAuto, className),
			style: span === 'auto' ? style : withVars(style, responsiveVars('span', span)),
			...rest,
		},
		children,
	);

type GridCompound = React.FC<GridProps> & { Item: React.FC<GridItemProps> };

/**
 * CSS grid with responsive column counts and spans.
 * Replaces Bootstrap `.row` / `.col-*`:
 *
 *   <Grid columns={12}>
 *     <Grid.Item span={{ base: 12, sm: 6, md: 4 }}>...</Grid.Item>
 *   </Grid>
 */
export const Grid = GridRoot as GridCompound;
Grid.Item = GridItem;

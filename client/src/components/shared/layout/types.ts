import type * as React from 'react';

/** Spacing scale from src/styles/00-settings/spacing. */
export type SpacingToken = 'none' | '3xs' | '2xs' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl';

/** Breakpoint names from src/styles/00-settings/breakpoints (min-width). */
export type Breakpoint = 'base' | 'sm' | 'md' | 'lg';

export type Responsive<T> = T | Partial<Record<Breakpoint, T>>;

export type LayoutAlign = 'start' | 'center' | 'end' | 'baseline' | 'stretch';
export type LayoutJustify = 'start' | 'center' | 'end' | 'between' | 'around' | 'evenly';

export type LayoutElement =
	| 'div'
	| 'section'
	| 'article'
	| 'aside'
	| 'header'
	| 'footer'
	| 'main'
	| 'nav'
	| 'ul'
	| 'ol'
	| 'li'
	| 'form'
	| 'fieldset'
	| 'p'
	| 'span';

export interface LayoutBaseProps extends React.HTMLAttributes<HTMLElement> {
	/** Element to render. Defaults to `div`. */
	as?: LayoutElement;
	children?: React.ReactNode;
}

export const spacingVar = (token: SpacingToken | undefined): string | undefined =>
	token === undefined ? undefined : token === 'none' ? '0' : `var(--spacing-${token})`;

const alignValue: Record<LayoutAlign, string> = {
	start: 'flex-start',
	center: 'center',
	end: 'flex-end',
	baseline: 'baseline',
	stretch: 'stretch',
};

const justifyValue: Record<LayoutJustify, string> = {
	start: 'flex-start',
	center: 'center',
	end: 'flex-end',
	between: 'space-between',
	around: 'space-around',
	evenly: 'space-evenly',
};

export const alignCss = (align: LayoutAlign | undefined) => (align ? alignValue[align] : undefined);
export const justifyCss = (justify: LayoutJustify | undefined) => (justify ? justifyValue[justify] : undefined);

/**
 * Expand a responsive value into `--<prefix>-<breakpoint>` custom properties.
 * A plain value becomes `--<prefix>-base`.
 */
export const responsiveVars = <T extends string | number>(
	prefix: string,
	value: Responsive<T> | undefined,
): Record<string, string> => {
	if (value === undefined) return {};
	if (typeof value !== 'object') return { [`--${prefix}-base`]: String(value) };
	const vars: Record<string, string> = {};
	for (const [bp, v] of Object.entries(value)) {
		if (v !== undefined) vars[`--${prefix}-${bp}`] = String(v);
	}
	return vars;
};

/** Merge custom-property maps into a style object without fighting the CSSProperties type. */
export const withVars = (
	style: React.CSSProperties | undefined,
	vars: Record<string, string | undefined>,
): React.CSSProperties | undefined => {
	const entries = Object.entries(vars).filter(([, v]) => v !== undefined);
	if (entries.length === 0) return style;
	return { ...style, ...Object.fromEntries(entries) } as React.CSSProperties;
};

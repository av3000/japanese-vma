import * as React from 'react';
import classNames from 'classnames';
import { capitalize } from '@/helpers';
import styles from './Link.module.scss';
import { LinkBaseProps, LinkColor, LinkSize, LinkWeightType } from './types';

export interface LinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
	readonly textLink?: boolean;
	readonly richTextLink?: boolean;
	readonly color?: LinkColor;
	readonly size?: LinkSize;
	readonly weight?: LinkWeightType;
	readonly hasMinHeight?: boolean;
}

export const useLinkClassNames = (
	{ richTextLink, hasMinHeight, color, size, weight, textLink }: LinkBaseProps,
	className?: string,
): string => {
	return classNames(
		styles.link,
		{
			[styles.textLink]: textLink,
			[styles.richTextLink]: richTextLink,
			[styles.hasMinHeight]: hasMinHeight,
		},
		color && styles['color' + capitalize(color)],
		size && styles['size' + capitalize(size)],
		weight && styles['weight' + capitalize(weight)],
		className,
	);
};

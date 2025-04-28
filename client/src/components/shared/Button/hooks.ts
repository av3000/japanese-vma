import classNames from 'classnames';
import styles from './Button.module.scss';
import { ButtonBaseProps } from './types';

const capitalize = (word: string): string => (word.length > 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word);

export const useButtonClassNames = (
	{
		hasNoPaddingX,
		hasOnlyIcon,
		isFullWidth,
		ctaGroupPos,
		disabled,
		isLoading,
		size,
		variant,
	}: Omit<ButtonBaseProps, 'aria-label'>,
	className?: string,
): string => {
	const getVariantClassName = (variant: string): string => {
		if (variant.includes('-')) {
			// For variants like 'secondary-outline'
			const parts = variant.split('-');
			const capitalizedParts = parts.map((part) => capitalize(part));
			return styles[`variant${capitalizedParts.join('')}`]; // -> variantSecondaryOutline
		}
		// For simple variants like 'ghost'
		return styles[`variant${capitalize(variant)}`]; // -> variantGhost
	};

	return classNames(
		{
			[styles.button]: true,
			[styles.fullWidth]: isFullWidth,
			[styles.hasOnlyIcon]: hasOnlyIcon,
			[styles.hasNoPaddingX]: hasNoPaddingX,
			[styles.disabled]: disabled || isLoading,
			[`u-cta-group-${ctaGroupPos}`]: ctaGroupPos !== undefined,
		},
		variant !== undefined && getVariantClassName(variant),
		size !== undefined && styles[`size${capitalize(size)}`],
		className,
	);
};

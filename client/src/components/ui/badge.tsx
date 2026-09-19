import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import classNames from 'classnames';
import styles from './badge.module.css';
import { STATUS_VARIANT_CLASSES } from './status-colors';

export const badgeVariantNames = [
	'default',
	'secondary',
	'success',
	'destructive',
	'outline',
	'ghost',
	'link',
	'pending',
] as const;

export type BadgeVariant = (typeof badgeVariantNames)[number];

const variantClass: Record<BadgeVariant, string> = {
	default: styles.default,
	secondary: styles.secondary,
	success: STATUS_VARIANT_CLASSES.success,
	destructive: STATUS_VARIANT_CLASSES.destructive,
	outline: styles.outline,
	ghost: styles.ghost,
	link: styles.link,
	pending: STATUS_VARIANT_CLASSES.pending,
};

/**
 * Class names for a badge variant. Kept for callers that composed `badgeVariants({ variant })`
 * when the component was class-variance-authority based.
 */
export function badgeVariants({ variant = 'default' }: { variant?: BadgeVariant | null } = {}) {
	return classNames(styles.badge, variantClass[variant ?? 'default']);
}

export interface BadgeProps extends React.ComponentProps<'span'> {
	variant?: BadgeVariant | null;
	/** Render the child element with badge styling instead of a `span`. */
	asChild?: boolean;
	/** Round, fixed-size badge that holds a single icon. Pair with `aria-label`. */
	isOnlyIcon?: boolean;
}

function Badge({ className, variant = 'default', asChild = false, isOnlyIcon = false, ...props }: BadgeProps) {
	const Comp = asChild ? Slot : 'span';
	const resolvedVariant = variant ?? 'default';

	return (
		<Comp
			data-slot="badge"
			data-variant={resolvedVariant}
			data-icon-only={isOnlyIcon ? '' : undefined}
			className={classNames(
				badgeVariants({ variant: resolvedVariant }),
				isOnlyIcon && styles.iconOnly,
				className,
			)}
			{...props}
		/>
	);
}

export { Badge };

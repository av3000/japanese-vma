import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { STATUS_VARIANT_BASE_CLASSES } from '@/components/ui/status-colors';

const badgeVariants = cva(
	'inline-flex items-center justify-center rounded-full border border-transparent px-2 py-0.5 text-[12px] font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden',
	{
		variants: {
			variant: {
				default: 'bg-primary text-primary-foreground hover:bg-primary/90',
				secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/90',
				success: `${STATUS_VARIANT_BASE_CLASSES.success} hover:bg-success/90`,
				destructive:
					`${STATUS_VARIANT_BASE_CLASSES.destructive} hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60`,
				outline: 'border-border text-foreground hover:bg-accent hover:text-accent-foreground',
				ghost: 'hover:bg-accent hover:text-accent-foreground',
				link: 'text-primary underline-offset-4 hover:underline',
				pending: `${STATUS_VARIANT_BASE_CLASSES.pending} hover:bg-warning/90`,
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	},
);

function Badge({
	className,
	variant = 'default',
	asChild = false,
	isOnlyIcon = false,
	...props
}: React.ComponentProps<'span'> &
	VariantProps<typeof badgeVariants> & { asChild?: boolean; isOnlyIcon?: boolean }) {
	const Comp = asChild ? Slot : 'span';

	return (
		<Comp
			data-slot="badge"
			data-variant={variant}
			data-icon-only={isOnlyIcon ? '' : undefined}
			className={cn(
				badgeVariants({ variant }),
				isOnlyIcon &&
					'h-8 w-8 p-0 rounded-full justify-center gap-0 [&>svg]:size-4 [&>svg]:m-0',
				className,
			)}
			{...props}
		/>
	);
}

export { Badge, badgeVariants };

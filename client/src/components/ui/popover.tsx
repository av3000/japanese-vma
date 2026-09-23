import * as React from 'react';
import * as PopoverPrimitive from '@radix-ui/react-popover';
import classNames from 'classnames';
import styles from './popover.module.css';

/**
 * Local seam over the Radix popover primitive. Callers only depend on these
 * components, so the underlying implementation can change without touching them.
 */
function Popover({ ...props }: React.ComponentProps<typeof PopoverPrimitive.Root>) {
	return <PopoverPrimitive.Root data-slot="popover" {...props} />;
}

function PopoverTrigger({ ...props }: React.ComponentProps<typeof PopoverPrimitive.Trigger>) {
	return <PopoverPrimitive.Trigger data-slot="popover-trigger" {...props} />;
}

function PopoverContent({
	className,
	align = 'center',
	sideOffset = 4,
	...props
}: React.ComponentProps<typeof PopoverPrimitive.Content>) {
	return (
		<PopoverPrimitive.Portal>
			<PopoverPrimitive.Content
				data-slot="popover-content"
				align={align}
				sideOffset={sideOffset}
				className={classNames(styles.content, className)}
				{...props}
			/>
		</PopoverPrimitive.Portal>
	);
}

function PopoverAnchor({ ...props }: React.ComponentProps<typeof PopoverPrimitive.Anchor>) {
	return <PopoverPrimitive.Anchor data-slot="popover-anchor" {...props} />;
}

function PopoverHeader({ className, ...props }: React.ComponentProps<'div'>) {
	return <div data-slot="popover-header" className={classNames(styles.header, className)} {...props} />;
}

function PopoverTitle({ className, ...props }: React.ComponentProps<'h2'>) {
	// Rendered as a div (as before) so the popover does not inject a heading level into the page outline.
	return <div data-slot="popover-title" className={classNames(styles.title, className)} {...props} />;
}

function PopoverDescription({ className, ...props }: React.ComponentProps<'p'>) {
	return <p data-slot="popover-description" className={classNames(styles.description, className)} {...props} />;
}

export { Popover, PopoverAnchor, PopoverContent, PopoverDescription, PopoverHeader, PopoverTitle, PopoverTrigger };

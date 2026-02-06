export const buttonVariants = [
	'primary',
	'secondary',
	'ghost',
	'outline',
	'secondary-outline',
	'pending',
	'danger',
	'success',
	'linkButton',
] as const;
export type ButtonVariant = (typeof buttonVariants)[number];

export const buttonSizes = ['sm', 'md', 'lg'] as const;

export type ButtonSize = (typeof buttonSizes)[number];

export interface ButtonCommonBaseProps {
	readonly disabled?: boolean;
	readonly isLoading?: boolean;
	readonly variant?: ButtonVariant;
	readonly size?: ButtonSize;
	readonly isFullWidth?: boolean;
	readonly ctaGroupPos?: 'left' | 'center' | 'right';
	readonly hasNoPaddingX?: boolean;
}

// Regular button interface (without icon-only)
export interface ButtonWithTextProps extends ButtonCommonBaseProps {
	hasOnlyIcon?: false;
	'aria-label'?: string; // Optional for text buttons
}

// Icon-only button interface
export interface ButtonWithIconOnlyProps extends ButtonCommonBaseProps {
	hasOnlyIcon: true;
	'aria-label'?: string; // Required for icon-only buttons
}

export type ButtonBaseProps = ButtonWithTextProps | ButtonWithIconOnlyProps;

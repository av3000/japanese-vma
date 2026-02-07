export const STATUS_VARIANT_BASE_CLASSES = {
	success: 'bg-success text-success-foreground',
	pending: 'bg-warning text-warning-foreground',
	destructive: 'bg-destructive text-destructive-foreground',
} as const;

export type StatusVariant = keyof typeof STATUS_VARIANT_BASE_CLASSES;


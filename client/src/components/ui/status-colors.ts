import styles from './badge.module.css';

/**
 * Classes for the badge variants that express an operation status. Each class sets the
 * background and text colour, so they also work on plain elements outside `Badge`.
 * Shared by `Badge` and ProcessingStatusAlert so the colours stay in one place.
 */
export const STATUS_VARIANT_CLASSES = {
	success: styles.success,
	pending: styles.pending,
	destructive: styles.destructive,
} as const;

export type StatusVariant = keyof typeof STATUS_VARIANT_CLASSES;

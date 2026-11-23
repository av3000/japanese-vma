import * as React from 'react';
import classNames from 'classnames';
import styles from './Alert.module.css';

export type AlertTone = 'info' | 'success' | 'warning' | 'danger';

export interface AlertProps extends React.HTMLAttributes<HTMLDivElement> {
	/** Semantic colour. Defaults to `info`. */
	tone?: AlertTone;
	/** Optional bold heading rendered above the body. */
	heading?: React.ReactNode;
	/** Optional trailing controls (retry, dismiss). */
	actions?: React.ReactNode;
}

const toneClass: Record<AlertTone, string> = {
	info: styles.info,
	success: styles.success,
	warning: styles.warning,
	danger: styles.danger,
};

/**
 * Inline status message. Replaces Bootstrap `.alert .alert-*`.
 * `danger`/`warning` announce as `role="alert"`, the rest as `role="status"`.
 */
export const Alert: React.FC<AlertProps> = ({
	tone = 'info',
	heading,
	actions,
	className,
	children,
	role,
	...rest
}) => (
	<div
		role={role ?? (tone === 'danger' || tone === 'warning' ? 'alert' : 'status')}
		className={classNames(styles.alert, toneClass[tone], className)}
		{...rest}
	>
		<div className={styles.body}>
			{heading && <p className={styles.title}>{heading}</p>}
			{children}
		</div>
		{actions && <div className={styles.actions}>{actions}</div>}
	</div>
);

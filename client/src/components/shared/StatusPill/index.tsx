import * as React from 'react';
import classNames from 'classnames';
import { Icon, type IconName } from '@/components/shared/Icon';
import Spinner from '@/components/shared/Spinner';
import styles from './StatusPill.module.css';
import type { StatusPillIcon, StatusTone } from './tones';

export interface StatusPillProps extends Omit<React.HTMLAttributes<HTMLSpanElement>, 'children'> {
	tone: StatusTone;
	/** Always rendered as visible text: colour and icon alone are not enough of a cue. */
	label: string;
	/** Defaults to the tone's own icon. */
	icon?: StatusPillIcon;
}

const TONE_ICONS: Record<StatusTone, IconName> = {
	neutral: 'minusSolid',
	success: 'checkSolid',
	warning: 'minusSolid',
	info: 'eyeRegular',
	danger: 'removeSolid',
};

/**
 * Small status marker: a leading icon (or spinner) and a text label on a tone tint. Feed it from
 * `articleStatusPill` or `processingStatusPill` so every status reads the same across the app.
 */
export const StatusPill: React.FC<StatusPillProps> = ({ tone, label, icon, className, ...rest }) => {
	const resolvedIcon = icon ?? TONE_ICONS[tone];

	return (
		<span className={classNames(styles.pill, styles[tone], className)} {...rest}>
			<span className={styles.icon} aria-hidden="true" data-icon={resolvedIcon}>
				{resolvedIcon === 'spinner' ? <Spinner size="sm" /> : <Icon size="sm" name={resolvedIcon} />}
			</span>
			<span className={styles.label}>{label}</span>
		</span>
	);
};

export { STATUS_TONES } from './tones';
export type { StatusPillIcon, StatusTone } from './tones';
export { articleStatusPill, processingStatusPill } from './statusPills';
export type { ArticleStatusPill, ProcessingStatusPill } from './statusPills';

export default StatusPill;

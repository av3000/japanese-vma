import * as React from 'react';
import classNames from 'classnames';
import { Icon, type IconSize } from '@/components/shared/Icon';
import { useTheme } from '@/themes/AppearanceProvider';
import styles from './LikeIcon.module.css';

export interface LikeIconProps {
	/** Whether the viewer likes the item. Ignored for `tone="count"`. */
	liked?: boolean;
	/**
	 * `state` (default): the like button's icon, outline when not liked and filled when liked.
	 * `count`: next to a likes count, always the filled shape in the muted like colour.
	 */
	tone?: 'state' | 'count';
	size?: IconSize;
	className?: string;
}

/**
 * The like icon from the theme's slots (kinari: thumbs up; sakura: a blossom). The state is never
 * colour alone: the shape changes too, and the button around it carries `aria-pressed`. `data-state` is
 * `on` or `off` on the like button's icon only; a likes count has `data-tone="count"` and no state.
 */
export const LikeIcon: React.FC<LikeIconProps> = ({ liked = false, tone = 'state', size = 'sm', className }) => {
	const { icons } = useTheme();
	const on = tone === 'state' && liked;

	return (
		<span
			className={classNames(styles.like, on ? styles.on : styles.off, className)}
			data-icon-slot="like"
			data-tone={tone}
			data-state={tone === 'state' ? (on ? 'on' : 'off') : undefined}
		>
			<Icon size={size} name={on || tone === 'count' ? icons.likeOn : icons.likeOff} />
		</span>
	);
};

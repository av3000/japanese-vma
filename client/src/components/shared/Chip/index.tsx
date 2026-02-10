import React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { ButtonBaseProps } from '@/components/shared/Button/types';
import { Icon } from '@/components/shared/Icon';
import styles from './Chip.module.scss';

interface ChipProps {
	/**
	 * Callback when the remove icon is clicked (not required for readonly chips)
	 */
	onCancel?: () => void;
	children: React.ReactNode;

	/**
	 * Additional CSS class for the chip
	 */
	className?: string;
	disabled?: boolean;
	title?: string;
	name?: string;
	value?: string;

	/**
	 * Button variant to use
	 * @default 'secondary-outline'
	 */
	variant?: ButtonBaseProps['variant'];

	/**
	 * If true, the chip will not have a remove icon and won't be removable
	 * @default false
	 */
	readonly?: boolean;
	onClick?: () => void;
}

/**
 * Generic Chip component, used for visualizing active filters,
 * tags, or other small pieces of information.
 */
export const Chip: React.FunctionComponent<ChipProps> = ({
	children,
	className,
	onCancel,
	disabled,
	title,
	name,
	value,
	variant = 'secondary-outline',
	readonly = false,
	onClick,
}) => {
	const handleClick = () => {
		if (readonly && onClick) {
			onClick();
		} else if (!readonly && onCancel) {
			onCancel();
		}
	};

	return (
		<Button
			title={title}
			name={name}
			value={value}
			className={classNames(styles.wrapper, { [styles.readonly]: readonly }, className)}
			variant={variant}
			size={'sm'}
			disabled={disabled}
			onClick={handleClick}
		>
			<div className={classNames(styles.chipTitle, 'u-ellipsis')}>{children}</div>
			{!readonly && <Icon className={'u-ml-2xs'} name={'removeSolid'} size={'sm'} />}
		</Button>
	);
};

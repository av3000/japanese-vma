import React from 'react';
import classNames from 'classnames';
import { Button } from '../Button';
import { Icon } from '../Icon';
import styles from './Chip.module.scss';

interface ChipProps {
	onCancel: () => void;
	children: React.ReactNode;
	disabled?: boolean;
	title: string;
	name?: string;
	value?: string;
}

/**
 * Generic Chip component, used for e.g. visualizing active filters
 */
export const Chip: React.FunctionComponent<ChipProps> = ({ children, onCancel, disabled, title, name, value }) => {
	return (
		<Button
			title={title}
			name={name}
			value={value}
			className={styles.wrapper}
			variant={'secondary-outline'}
			size={'sm'}
			disabled={disabled}
			onClick={onCancel}
		>
			<div className={classNames(styles.chipTitle, 'u-ellipsis')}>{children}</div>
			<Icon className={'u-ml-2xs'} name={'removeSolid'} size={'sm'} />
		</Button>
	);
};

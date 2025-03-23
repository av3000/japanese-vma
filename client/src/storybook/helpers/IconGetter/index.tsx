import React from 'react';
import { icons } from '@/assets/icons';
import { Icon, IconName } from '@/components/shared/Icon';
import styles from './IconGetter.module.scss';

export const IconGetter: React.FunctionComponent = () => (
	<ul className={styles.list}>
		{Object.keys(icons).map((icon, index) => (
			<li className={styles.item} key={index}>
				<Icon name={icon as IconName} size={'lg'} />
				<p className={styles.label}>{icon}</p>
			</li>
		))}
	</ul>
);

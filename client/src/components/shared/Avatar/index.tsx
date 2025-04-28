import React from 'react';
import DefaultImage from '@/assets/images/default-avatar.svg';
import Image from '../Image';
import styles from './Avatar.module.scss';

export interface AvatarProps {
	/* eslint-disable */
	image: Partial<any>;
	linkTitle: string;
	linkUrl?: string;
	size?: 'sm' | 'md' | 'lg';
	lazyLoading?: boolean;
}

export const Avatar: React.FunctionComponent<AvatarProps> = ({ image, linkTitle, linkUrl, lazyLoading }) => {
	const imageObj = {
		size180: image.size180 ? image.size180 : { url: DefaultImage },
		size320: image.size320 ? image.size320 : undefined,
		size480: image.size480 ? image.size480 : undefined,
		size720: image.size720 ? image.size720 : undefined,
		size960: image.size960 ? image.size960 : undefined,
		size1280: image.size1280 ? image.size1280 : undefined,
		size1920: image.size1920 ? image.size1920 : undefined,
	};

	return (
		<a className={styles.clickArea} href={linkUrl}>
			<div className={styles.imageWrapper}>
				<Image
					className={styles.image}
					{...imageObj}
					altText={image.altText ?? linkTitle}
					responsiveSizesSettings={'100px'}
					lazyLoading={lazyLoading}
				/>
			</div>
		</a>
	);
};

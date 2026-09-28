import * as React from 'react';
import classNames from 'classnames';
import band640Jpg from '@/assets/images/home/fuji-band-640.jpg';
import band640Webp from '@/assets/images/home/fuji-band-640.webp';
import band1280Jpg from '@/assets/images/home/fuji-band-1280.jpg';
import band1280Webp from '@/assets/images/home/fuji-band-1280.webp';
import band1920Jpg from '@/assets/images/home/fuji-band-1920.jpg';
import band1920Webp from '@/assets/images/home/fuji-band-1920.webp';
import styles from './PhotoBand.module.css';

export const PHOTO_CREDIT = {
	label: 'Photo: Ningyu · Unsplash',
	href: 'https://unsplash.com/photos/mount-fuji-peak-in-black-and-white-tb49PTdW1ZM',
} as const;

/** Matches the default (`lg`) `Container`: 16px gutters, 24px from 1320px, capped at 1440px. */
const CONTAINER_SIZES = '(min-width: 1488px) 1440px, (min-width: 1320px) calc(100vw - 48px), calc(100vw - 32px)';

export interface PhotoBandProps {
	/** Override when the band sits in something other than a default `Container`. */
	sizes?: string;
	className?: string;
}

/**
 * A shallow decorative photo band (Mount Fuji) with a visible credit. Pre-resized WebP with a
 * JPG fallback; the band's height is fixed in CSS, so the lazy-loaded image causes no layout shift.
 */
export const PhotoBand: React.FC<PhotoBandProps> = ({ sizes = CONTAINER_SIZES, className }) => (
	<div className={classNames(styles.band, className)}>
		<picture>
			<source
				type="image/webp"
				srcSet={`${band640Webp} 640w, ${band1280Webp} 1280w, ${band1920Webp} 1920w`}
				sizes={sizes}
			/>
			<img
				className={styles.image}
				src={band1280Jpg}
				srcSet={`${band640Jpg} 640w, ${band1280Jpg} 1280w, ${band1920Jpg} 1920w`}
				sizes={sizes}
				width={1920}
				height={384}
				alt=""
				loading="lazy"
				decoding="async"
			/>
		</picture>
		<a className={styles.credit} href={PHOTO_CREDIT.href}>
			{PHOTO_CREDIT.label}
		</a>
	</div>
);

import * as React from 'react';
import classNames from 'classnames';
import { useTheme } from '@/themes/AppearanceProvider';
import styles from './PhotoBand.module.css';

/** Matches the default (`lg`) `Container`: 16px gutters, 24px from 1320px, capped at 1440px. */
const CONTAINER_SIZES = '(min-width: 1488px) 1440px, (min-width: 1320px) calc(100vw - 48px), calc(100vw - 32px)';

export interface PhotoBandProps {
	/** Override when the band sits in something other than a default `Container`. */
	sizes?: string;
	className?: string;
}

/**
 * A shallow decorative image band, from the theme's `hero` slot (kinari: Mount Fuji, with a visible
 * credit). The band's height is fixed in CSS, so the lazy-loaded image causes no layout shift.
 */
export const PhotoBand: React.FC<PhotoBandProps> = ({ sizes = CONTAINER_SIZES, className }) => {
	const { hero } = useTheme();

	return (
		<div className={classNames(styles.band, className)}>
			<picture>
				{hero.sources.map((source) => (
					<source key={source.type} type={source.type} srcSet={source.srcSet} sizes={sizes} />
				))}
				<img
					className={styles.image}
					src={hero.src}
					srcSet={hero.srcSet}
					sizes={hero.srcSet ? sizes : undefined}
					width={hero.width}
					height={hero.height}
					alt=""
					loading="lazy"
					decoding="async"
				/>
			</picture>
			{hero.credit && (
				<a className={styles.credit} href={hero.credit.href}>
					{hero.credit.label}
				</a>
			)}
		</div>
	);
};

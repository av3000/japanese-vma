import * as React from 'react';
import classNames from 'classnames';
import { useTheme } from '@/themes/AppearanceProvider';
import styles from './Logo.module.css';

export const BRAND_NAME = 'JPLearning';

/**
 * The brand: the theme's mark (if it has one) and the "JPLearning" wordmark. The mark is decorative,
 * so a link around the logo is named by the visible text in every theme.
 */
export const Logo: React.FC<{ className?: string }> = ({ className }) => {
	const { logo } = useTheme();

	if (!logo) {
		return <>{BRAND_NAME}</>;
	}

	return (
		<span className={classNames(styles.logo, className)}>
			<img className={styles.mark} src={logo.src} width={logo.width} height={logo.height} alt="" />
			{BRAND_NAME}
		</span>
	);
};

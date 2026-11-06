import * as React from 'react';
import classNames from 'classnames';
import { Label, Select } from '@/components/shared/FormControls';
import { useAppearance } from '@/themes/AppearanceProvider';
import { isThemeId, THEME_IDS, themeManifest } from '@/themes/manifest';
import styles from './ThemePicker.module.css';

/**
 * Chooses the theme (#498). A native select, so it works the same for keyboard, touch and screen
 * readers; the choice is saved per browser for guests and members alike. A theme changes appearance
 * only, so nothing else on the page depends on it.
 */
export const ThemePicker: React.FC<{ className?: string }> = ({ className }) => {
	const { theme, setTheme } = useAppearance();
	const id = React.useId();

	return (
		<div className={classNames(styles.picker, className)}>
			<Label htmlFor={id} className={styles.label}>
				Theme
			</Label>
			<Select
				id={id}
				size="sm"
				className={styles.select}
				value={theme}
				onChange={(event) => {
					if (isThemeId(event.target.value)) {
						setTheme(event.target.value);
					}
				}}
			>
				{THEME_IDS.map((option) => (
					<option key={option} value={option}>
						{themeManifest[option].label}
					</option>
				))}
			</Select>
		</div>
	);
};

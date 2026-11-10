import * as React from 'react';
import classNames from 'classnames';
import { Icon } from '@/components/shared/Icon';
import { useAppearance, useTheme } from '@/themes/AppearanceProvider';
import styles from './Header.module.css';

/**
 * The light/dark switch at the right end of the header, for guests and members alike (#498).
 *
 * A toggle button with a fixed name, "Dark mode": screen readers announce its pressed state, so the
 * label never changes too. The icon shows the scheme on screen, drawn from the theme's slots, and the
 * tooltip says what a click does. Not rendered for a single-scheme theme; hidden by CSS in a browser
 * without light-dark(), where the page stays light.
 */
export const ColorSchemeToggle: React.FC<{ className?: string }> = ({ className }) => {
	const { scheme, canToggleScheme, toggleScheme } = useAppearance();
	const { icons } = useTheme();

	if (!canToggleScheme) {
		return null;
	}

	const isDark = scheme === 'dark';

	return (
		<button
			type="button"
			className={classNames(styles.iconButton, styles.schemeToggle, className)}
			aria-pressed={isDark}
			aria-label="Dark mode"
			title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
			onClick={toggleScheme}
		>
			<Icon size="sm" name={isDark ? icons.schemeDark : icons.schemeLight} />
		</button>
	);
};

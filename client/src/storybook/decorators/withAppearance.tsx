import * as React from 'react';
import type { Decorator } from '@storybook/react-vite';
import { useGlobals } from 'storybook/preview-api';
import { applyAppearance } from '@/lib/appearance';
import { AppearanceContext, type AppearanceContextValue } from '@/themes/AppearanceProvider';
import {
	DEFAULT_THEME,
	isSchemeName,
	isThemeId,
	resolveScheme,
	type SchemeName,
	themeManifest,
	type ThemeId,
} from '@/themes/manifest';
import { themeRegistry } from '@/themes/registry';

/** Pins a story to a theme and/or scheme, whatever the toolbar says. */
export interface AppearanceParameter {
	theme?: ThemeId;
	scheme?: SchemeName;
}

export const resolveStoryAppearance = (
	parameter: AppearanceParameter | undefined,
	globals: { theme?: unknown; scheme?: unknown },
) => {
	const theme = isThemeId(parameter?.theme)
		? parameter.theme
		: isThemeId(globals.theme)
			? globals.theme
			: DEFAULT_THEME;
	const wanted = parameter?.scheme ?? globals.scheme;
	const canToggleScheme = themeManifest[theme].schemes.length > 1;
	// The toolbar's scheme stands in for a saved choice; with none, a story shows light, not the OS.
	const scheme = resolveScheme(theme, isSchemeName(wanted) ? wanted : null, 'light');

	return { theme, scheme, savedScheme: canToggleScheme ? scheme : null, canToggleScheme };
};

/** Writes the attributes before the story paints, and loads the theme's fonts, outside render. */
const AppearanceFrame: React.FC<{ value: AppearanceContextValue; children: React.ReactNode }> = ({
	value,
	children,
}) => {
	const { theme, scheme, savedScheme } = value;

	React.useLayoutEffect(() => applyAppearance({ theme, scheme, savedScheme }), [theme, scheme, savedScheme]);

	React.useEffect(() => {
		void themeRegistry[theme].loadFonts?.();
	}, [theme]);

	return <AppearanceContext.Provider value={value}>{children}</AppearanceContext.Provider>;
};

/**
 * Sets `data-theme` and `data-scheme` on the preview's <html> and provides the appearance context, from
 * the toolbar's `theme` and `scheme` globals. `parameters.appearance` wins over the toolbar. The scheme
 * toggle and the theme picker inside a story update the toolbar.
 */
export const withAppearance: Decorator = (Story, { parameters, globals }) => {
	// Storybook's preview hook (storybook/preview-api), which decorators may call; not a React hook.
	// eslint-disable-next-line react-hooks/rules-of-hooks
	const [, updateGlobals] = useGlobals();
	const state = resolveStoryAppearance(parameters.appearance as AppearanceParameter | undefined, globals);

	const value: AppearanceContextValue = {
		...state,
		setTheme: (theme) => updateGlobals({ theme }),
		toggleScheme: () => updateGlobals({ scheme: state.scheme === 'dark' ? 'light' : 'dark' }),
	};

	return (
		<AppearanceFrame value={value}>
			<Story />
		</AppearanceFrame>
	);
};

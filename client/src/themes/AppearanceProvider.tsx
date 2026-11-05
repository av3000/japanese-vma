import * as React from 'react';
import {
	type AppearanceState,
	applyAppearance,
	getAppearance,
	setTheme,
	subscribeAppearance,
	toggleScheme,
} from '@/lib/appearance';
import { DEFAULT_THEME, type ThemeId } from './manifest';
import { type ThemeConfig, themeRegistry } from './registry';

export interface AppearanceContextValue extends AppearanceState {
	setTheme: (theme: ThemeId) => void;
	toggleScheme: () => void;
}

/** Outside a provider (unit tests, isolated renders): kinari, light, and controls that do nothing. */
export const DEFAULT_APPEARANCE: AppearanceContextValue = {
	theme: DEFAULT_THEME,
	scheme: 'light',
	savedScheme: null,
	canToggleScheme: true,
	setTheme: () => undefined,
	toggleScheme: () => undefined,
};

/** Storybook's decorator provides its own value from the toolbar; the app uses `AppearanceProvider`. */
export const AppearanceContext = React.createContext<AppearanceContextValue>(DEFAULT_APPEARANCE);

/** Connects the app to the appearance store (localStorage, the OS scheme, other tabs). */
export const AppearanceProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
	const state = React.useSyncExternalStore(subscribeAppearance, getAppearance, getAppearance);

	// The boot script in index.html has normally applied this already; this covers a page without it.
	React.useEffect(() => applyAppearance(getAppearance()), []);

	const value = React.useMemo(() => ({ ...state, setTheme, toggleScheme }), [state]);

	return <AppearanceContext.Provider value={value}>{children}</AppearanceContext.Provider>;
};

/** The theme and scheme on screen, and the controls that change them. */
export const useAppearance = () => React.useContext(AppearanceContext);

/** The current theme's config: read its slots, never compare its id. */
export const useTheme = (): ThemeConfig => themeRegistry[useAppearance().theme];

/**
 * Theme registry: the manifest plus the slots that import assets (STYLING-03, #498).
 *
 * Components read a slot through `useTheme()` (`theme.icons.likeOn`, `theme.logo`), never the theme
 * id. What must differ in markup is a slot here; what must differ in behaviour is a named flag, never
 * a theme name.
 */
import { type ThemeId, type ThemeManifestEntry, themeManifest } from './manifest';

export type ThemeConfig = ThemeManifestEntry;

export const themeRegistry: Record<ThemeId, ThemeConfig> = {
	kinari: { ...themeManifest.kinari },
};

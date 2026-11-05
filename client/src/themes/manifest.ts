/**
 * Theme manifest: plain data about each theme, with no imports, so `index.html`'s boot script, the
 * ESLint config check and the app can all agree on it (STYLING-03, #498).
 *
 * A theme changes appearance only: colours, fonts, imagery, icons, favicon. It never changes
 * behaviour or which features exist. Code outside `src/themes/` never compares a theme id; it reads
 * a slot from `useTheme()` instead (`client/DESIGN.md` §3).
 */

export type SchemeName = 'light' | 'dark';

export const THEME_IDS = ['kinari'] as const;
export type ThemeId = (typeof THEME_IDS)[number];

export const DEFAULT_THEME: ThemeId = 'kinari';

/** localStorage keys. A missing scheme key means "follow the operating system". */
export const STORAGE_KEYS = {
	theme: 'jvma:theme',
	scheme: 'jvma:color-scheme',
} as const;

/**
 * Whether a theme with two schemes follows the OS when nothing is saved. Off until every theme has
 * been checked in both schemes (#498 slice 6); a saved choice works either way.
 */
export const FOLLOWS_OS_SCHEME = false;

export interface ThemeManifestEntry {
	id: ThemeId;
	/** Shown in the theme picker. */
	label: string;
	/** One entry: the scheme is forced and the scheme toggle is hidden. */
	schemes: readonly SchemeName[];
	/** The browser chrome colour per scheme, normally the theme's page surface. */
	themeColor: Partial<Record<SchemeName, string>>;
	/** Under `public/`. `png180` is the apple-touch-icon. */
	favicon: { svg: string; png180: string };
}

export const themeManifest: Record<ThemeId, ThemeManifestEntry> = {
	kinari: {
		id: 'kinari',
		label: 'Kinari',
		schemes: ['light', 'dark'],
		themeColor: { light: '#fbfaf5', dark: '#1c1b19' },
		favicon: { svg: '/themes/kinari/favicon.svg', png180: '/themes/kinari/apple-touch-icon.png' },
	},
};

export const isThemeId = (value: unknown): value is ThemeId =>
	typeof value === 'string' && (THEME_IDS as readonly string[]).includes(value);

export const isSchemeName = (value: unknown): value is SchemeName => value === 'light' || value === 'dark';

/**
 * The scheme on screen: a single-scheme theme's own scheme; otherwise the saved choice, else the
 * OS scheme while `FOLLOWS_OS_SCHEME` is on, else light.
 */
export const resolveScheme = (theme: ThemeId, saved: SchemeName | null, os: SchemeName): SchemeName => {
	const { schemes } = themeManifest[theme];

	if (schemes.length === 1) {
		return schemes[0];
	}

	return saved ?? (FOLLOWS_OS_SCHEME ? os : 'light');
};

/** The compact map `index.html`'s boot script embeds. A unit test keeps the two identical. */
export const bootMap = () => ({
	k: [STORAGE_KEYS.theme, STORAGE_KEYS.scheme],
	d: DEFAULT_THEME,
	o: FOLLOWS_OS_SCHEME,
	t: Object.fromEntries(
		THEME_IDS.map((id) => {
			const entry = themeManifest[id];
			return [id, { s: entry.schemes, c: entry.themeColor, f: entry.favicon.svg, a: entry.favicon.png180 }];
		}),
	),
});

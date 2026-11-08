/**
 * The appearance store: which theme and colour scheme are on screen (STYLING-03, #498).
 *
 * - The theme and an explicit scheme choice are saved per browser (`STORAGE_KEYS`). With no saved
 *   scheme the page follows the OS (once `FOLLOWS_OS_SCHEME` is on) and updates live.
 * - Toggling the scheme back to the OS scheme deletes the saved choice, so the user follows the OS again.
 * - Changing the theme never touches the saved scheme.
 * - If storage throws (private mode, blocked site data, full) the store keeps the choice in memory.
 * - In a browser without light-dark() a two-scheme theme stays light and the toggle is hidden.
 *
 * `index.html`'s boot script applies the same state before the first paint; `applyAppearance`
 * is the in-app equivalent and must write the same attributes.
 */
import {
	DEFAULT_THEME,
	isSchemeName,
	isThemeId,
	resolveScheme,
	type SchemeName,
	STORAGE_KEYS,
	themeManifest,
	type ThemeId,
} from '@/themes/manifest';

export interface AppearanceState {
	theme: ThemeId;
	/** The scheme on screen. */
	scheme: SchemeName;
	/** The saved scheme choice, or null when following the OS. */
	savedScheme: SchemeName | null;
	/** False for a single-scheme theme: the toggle is not shown. */
	canToggleScheme: boolean;
}

const OS_DARK_QUERY = '(prefers-color-scheme: dark)';

/** Choices this page made that storage refused to save. They win over storage until a save succeeds. */
const unsaved = new Map<string, string | null>();

const read = (key: string): string | null => {
	if (unsaved.has(key)) {
		return unsaved.get(key) ?? null;
	}

	try {
		return window.localStorage.getItem(key);
	} catch {
		return null;
	}
};

const write = (key: string, value: string | null) => {
	try {
		if (value === null) {
			window.localStorage.removeItem(key);
		} else {
			window.localStorage.setItem(key, value);
		}
		unsaved.delete(key);
	} catch {
		// Storage unavailable or full: keep the choice in memory for this page.
		unsaved.set(key, value);
	}
};

const osScheme = (): SchemeName =>
	typeof window !== 'undefined' && window.matchMedia?.(OS_DARK_QUERY).matches ? 'dark' : 'light';

/** Without light-dark() the CSS has only kinari's light values; index.html's boot script checks the same. */
const supportsLightDark = () =>
	typeof CSS !== 'undefined' && typeof CSS.supports === 'function' && CSS.supports('color', 'light-dark(#000, #fff)');

const computeState = (): AppearanceState => {
	const storedTheme = read(STORAGE_KEYS.theme);
	const theme = isThemeId(storedTheme) ? storedTheme : DEFAULT_THEME;
	const storedScheme = read(STORAGE_KEYS.scheme);
	const canToggleScheme = themeManifest[theme].schemes.length > 1 && supportsLightDark();
	const savedScheme = canToggleScheme && isSchemeName(storedScheme) ? storedScheme : null;
	// A two-scheme theme in a browser without light-dark() stays light, whatever the OS says.
	const os = canToggleScheme ? osScheme() : 'light';

	return { theme, scheme: resolveScheme(theme, savedScheme, os), savedScheme, canToggleScheme };
};

/** Writes the attributes, `theme-color` and favicon links for a state. Mirrors the boot script. */
export const applyAppearance = (state: Pick<AppearanceState, 'theme' | 'scheme' | 'savedScheme'>) => {
	const root = document.documentElement;
	const entry = themeManifest[state.theme];

	root.dataset.theme = state.theme;

	if (state.savedScheme && entry.schemes.length > 1) {
		root.dataset.scheme = state.savedScheme;
	} else {
		delete root.dataset.scheme;
	}

	const themeColor = entry.themeColor[state.scheme];
	const meta = document.getElementById('theme-color');

	if (meta && themeColor) {
		meta.setAttribute('content', themeColor);
	}

	document.getElementById('favicon')?.setAttribute('href', entry.favicon.svg);
	document.getElementById('touch-icon')?.setAttribute('href', entry.favicon.png180);
};

let state: AppearanceState | null = null;
const listeners = new Set<() => void>();

const refresh = () => {
	const next = computeState();

	if (
		!state ||
		next.theme !== state.theme ||
		next.scheme !== state.scheme ||
		next.savedScheme !== state.savedScheme
	) {
		state = next;
		applyAppearance(state);
		listeners.forEach((listener) => listener());
	}
};

export const getAppearance = (): AppearanceState => {
	if (!state) {
		state = computeState();
	}

	return state;
};

/** For `useSyncExternalStore`: listens to the OS scheme and to other tabs while anyone subscribes. */
export const subscribeAppearance = (listener: () => void) => {
	listeners.add(listener);

	const media = window.matchMedia?.(OS_DARK_QUERY);
	const onStorage = (event: StorageEvent) => {
		if (event.key === null || event.key === STORAGE_KEYS.theme || event.key === STORAGE_KEYS.scheme) {
			refresh();
		}
	};

	media?.addEventListener?.('change', refresh);
	window.addEventListener('storage', onStorage);

	return () => {
		listeners.delete(listener);
		media?.removeEventListener?.('change', refresh);
		window.removeEventListener('storage', onStorage);
	};
};

export const setTheme = (theme: ThemeId) => {
	write(STORAGE_KEYS.theme, isThemeId(theme) && theme !== DEFAULT_THEME ? theme : null);
	refresh();
};

/** Switches to the other scheme. Matching the OS deletes the saved choice. */
export const toggleScheme = () => {
	const current = getAppearance();

	if (!current.canToggleScheme) {
		return;
	}

	const next: SchemeName = current.scheme === 'dark' ? 'light' : 'dark';
	const followed = resolveScheme(current.theme, null, osScheme());

	write(STORAGE_KEYS.scheme, next === followed ? null : next);
	refresh();
};

/** Tests only: forget the cached state so the next read starts from storage and `matchMedia`. */
export const resetAppearanceForTests = () => {
	state = null;
	unsaved.clear();
	listeners.clear();
};

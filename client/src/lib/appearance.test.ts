/**
 * @vitest-environment jsdom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { STORAGE_KEYS } from '@/themes/manifest';
import { getAppearance, resetAppearanceForTests, setTheme, subscribeAppearance, toggleScheme } from './appearance';

let osDark = false;
const mediaListeners = new Set<() => void>();

const mockMatchMedia = () => {
	window.matchMedia = vi.fn().mockImplementation(() => ({
		get matches() {
			return osDark;
		},
		addEventListener: (_: string, listener: () => void) => mediaListeners.add(listener),
		removeEventListener: (_: string, listener: () => void) => mediaListeners.delete(listener),
	})) as unknown as typeof window.matchMedia;
};

const setOsDark = (dark: boolean) => {
	osDark = dark;
	mediaListeners.forEach((listener) => listener());
};

describe('appearance store', () => {
	beforeEach(() => {
		osDark = false;
		mediaListeners.clear();
		localStorage.clear();
		document.documentElement.removeAttribute('data-theme');
		document.documentElement.removeAttribute('data-scheme');
		document.head.innerHTML =
			'<meta id="theme-color" content=""><link id="favicon" href=""><link id="touch-icon" href="">';
		mockMatchMedia();
		resetAppearanceForTests();
	});

	afterEach(() => vi.restoreAllMocks());

	it('starts on kinari light with nothing saved', () => {
		expect(getAppearance()).toMatchObject({
			theme: 'kinari',
			scheme: 'light',
			savedScheme: null,
			canToggleScheme: true,
		});
	});

	it('toggles to dark, saves it and sets data-scheme', () => {
		toggleScheme();

		expect(getAppearance()).toMatchObject({ scheme: 'dark', savedScheme: 'dark' });
		expect(localStorage.getItem(STORAGE_KEYS.scheme)).toBe('dark');
		expect(document.documentElement.dataset.scheme).toBe('dark');
		expect(document.getElementById('theme-color')?.getAttribute('content')).toBe('#1c1b19');
	});

	it('toggling back to the followed scheme deletes the saved choice', () => {
		toggleScheme();
		toggleScheme();

		expect(getAppearance()).toMatchObject({ scheme: 'light', savedScheme: null });
		expect(localStorage.getItem(STORAGE_KEYS.scheme)).toBeNull();
		expect(document.documentElement.dataset.scheme).toBeUndefined();
	});

	it('changing the theme keeps the saved scheme', () => {
		toggleScheme();
		setTheme('kinari');

		expect(getAppearance()).toMatchObject({ theme: 'kinari', savedScheme: 'dark' });
		expect(localStorage.getItem(STORAGE_KEYS.scheme)).toBe('dark');
	});

	it('falls back to kinari for an unknown stored theme', () => {
		localStorage.setItem(STORAGE_KEYS.theme, 'removed-theme');

		expect(getAppearance().theme).toBe('kinari');
	});

	it('notifies subscribers when another tab changes the choice', () => {
		const listener = vi.fn();
		const unsubscribe = subscribeAppearance(listener);

		localStorage.setItem(STORAGE_KEYS.scheme, 'dark');
		window.dispatchEvent(new StorageEvent('storage', { key: STORAGE_KEYS.scheme }));

		expect(listener).toHaveBeenCalled();
		expect(getAppearance().scheme).toBe('dark');
		unsubscribe();
	});

	it('keeps working in memory when storage throws', () => {
		vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
			throw new Error('blocked');
		});
		vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
			throw new Error('blocked');
		});

		toggleScheme();

		expect(getAppearance()).toMatchObject({ scheme: 'dark', savedScheme: 'dark' });
	});

	it('uses the unsaved choice when storage reads but will not save', () => {
		vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
			throw new Error('quota');
		});

		toggleScheme();
		expect(getAppearance()).toMatchObject({ scheme: 'dark', savedScheme: 'dark' });

		toggleScheme();
		expect(getAppearance()).toMatchObject({ scheme: 'light', savedScheme: null });
	});

	it('stays light, with no toggle, in a browser without light-dark()', () => {
		vi.spyOn(CSS, 'supports').mockReturnValue(false);
		localStorage.setItem(STORAGE_KEYS.scheme, 'dark');
		osDark = true;

		expect(getAppearance()).toMatchObject({ scheme: 'light', savedScheme: null, canToggleScheme: false });
		expect(document.documentElement.hasAttribute('data-scheme')).toBe(false);
	});

	it('follows a dark OS with nothing saved, without setting data-scheme', () => {
		osDark = true;

		expect(getAppearance()).toMatchObject({ scheme: 'dark', savedScheme: null });
	});

	it('updates live when the OS scheme changes and nothing is saved', () => {
		const listener = vi.fn();
		const unsubscribe = subscribeAppearance(listener);
		getAppearance();

		setOsDark(true);

		expect(listener).toHaveBeenCalled();
		expect(getAppearance().scheme).toBe('dark');
		expect(document.getElementById('theme-color')?.getAttribute('content')).toBe('#1c1b19');
		unsubscribe();
	});

	it('toggling from a dark OS to light saves light; toggling back clears it', () => {
		osDark = true;
		toggleScheme();
		expect(getAppearance()).toMatchObject({ scheme: 'light', savedScheme: 'light' });

		toggleScheme();
		expect(getAppearance()).toMatchObject({ scheme: 'dark', savedScheme: null });
		expect(localStorage.getItem(STORAGE_KEYS.scheme)).toBeNull();
	});

	it('a single-scheme theme ignores a saved scheme and the OS', () => {
		localStorage.setItem(STORAGE_KEYS.theme, 'sumi');
		localStorage.setItem(STORAGE_KEYS.scheme, 'light');

		expect(getAppearance()).toMatchObject({
			theme: 'sumi',
			scheme: 'dark',
			savedScheme: null,
			canToggleScheme: false,
		});
		toggleScheme();
		expect(getAppearance().scheme).toBe('dark');
	});

	it('an OS change re-evaluates the scheme for subscribers', () => {
		const listener = vi.fn();
		const unsubscribe = subscribeAppearance(listener);
		toggleScheme();
		listener.mockClear();

		setOsDark(true);

		// A saved choice wins over the OS, so nothing on screen changes.
		expect(getAppearance().scheme).toBe('dark');
		unsubscribe();
	});
});

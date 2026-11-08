/**
 * @vitest-environment jsdom
 */
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { bootMap, STORAGE_KEYS } from './manifest';

/** The boot script in index.html, which applies the saved theme and scheme before the first paint. */
const html = readFileSync(resolve(__dirname, '../../index.html'), 'utf8');
const script = /<script>([\s\S]*?)<\/script>/.exec(html)?.[1] ?? '';

const HEAD = `
	<meta name="theme-color" id="theme-color" content="" />
	<link rel="icon" id="favicon" href="" />
	<link rel="apple-touch-icon" id="touch-icon" href="" />`;

const run = ({ theme, scheme, osDark = false }: { theme?: string; scheme?: string; osDark?: boolean }) => {
	if (theme !== undefined) localStorage.setItem(STORAGE_KEYS.theme, theme);
	if (scheme !== undefined) localStorage.setItem(STORAGE_KEYS.scheme, scheme);
	window.matchMedia = vi.fn().mockReturnValue({ matches: osDark }) as unknown as typeof window.matchMedia;
	new Function(script)();

	const root = document.documentElement;
	return {
		theme: root.getAttribute('data-theme'),
		scheme: root.getAttribute('data-scheme'),
		themeColor: document.getElementById('theme-color')?.getAttribute('content'),
		favicon: document.getElementById('favicon')?.getAttribute('href'),
	};
};

describe('index.html boot script', () => {
	beforeEach(() => {
		document.head.innerHTML = HEAD;
		document.documentElement.removeAttribute('data-theme');
		document.documentElement.removeAttribute('data-scheme');
		localStorage.clear();
	});

	afterEach(() => vi.restoreAllMocks());

	it('embeds exactly the manifest’s boot map', () => {
		const literal = /var m = (\{[\s\S]*?\});\s*function get/.exec(script)?.[1] ?? 'null';
		const embedded: unknown = new Function(`return ${literal};`)();

		expect(embedded, `Update the map in index.html to:\n${JSON.stringify(bootMap())}`).toEqual(bootMap());
	});

	it('defaults to kinari with no saved scheme', () => {
		expect(run({})).toEqual({
			theme: 'kinari',
			scheme: null,
			themeColor: '#fbfaf5',
			favicon: '/themes/kinari/favicon.svg',
		});
	});

	it('falls back to kinari for an unknown or inherited theme id', () => {
		expect(run({ theme: 'removed-theme' }).theme).toBe('kinari');
		document.documentElement.removeAttribute('data-theme');
		expect(run({ theme: '__proto__' }).theme).toBe('kinari');
	});

	it('applies a saved scheme and its theme-color', () => {
		expect(run({ scheme: 'dark' })).toMatchObject({ scheme: 'dark', themeColor: '#1c1b19' });
	});

	it('follows a dark OS when nothing is saved, without setting data-scheme', () => {
		expect(run({ osDark: true })).toMatchObject({ scheme: null, themeColor: '#1c1b19' });
	});

	it('a saved light scheme wins over a dark OS', () => {
		expect(run({ scheme: 'light', osDark: true })).toMatchObject({ scheme: 'light', themeColor: '#fbfaf5' });
	});

	it('applies a saved theme, and forces a single-scheme theme’s scheme over a saved one', () => {
		expect(run({ theme: 'sakura' })).toMatchObject({ theme: 'sakura', favicon: '/themes/sakura/favicon.svg' });
		document.documentElement.removeAttribute('data-theme');
		expect(run({ theme: 'sumi', scheme: 'light' })).toMatchObject({
			theme: 'sumi',
			scheme: null,
			themeColor: '#141311',
		});
	});

	it('stays light in a browser without light-dark(), whatever is saved or the OS says', () => {
		vi.spyOn(CSS, 'supports').mockReturnValue(false);

		expect(run({ scheme: 'dark', osDark: true })).toMatchObject({ scheme: null, themeColor: '#fbfaf5' });
		document.documentElement.removeAttribute('data-theme');
		expect(run({ theme: 'sumi' })).toMatchObject({ theme: 'sumi', themeColor: '#141311' });
	});

	it('ignores a scheme value it does not know', () => {
		expect(run({ scheme: 'sepia' }).scheme).toBeNull();
	});

	it('still runs when storage throws', () => {
		vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
			throw new Error('blocked');
		});

		expect(run({}).theme).toBe('kinari');
	});
});

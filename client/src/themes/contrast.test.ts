/**
 * Every role pair meets WCAG 2.2 AA in every theme × scheme (STYLING-03, #498, AC-6).
 *
 * The values come from the CSS itself: colors.css, roles.css and src/styles/themes/*.css are parsed,
 * `var()` and `light-dark()` are resolved per theme and scheme, and each pair's contrast ratio is
 * checked. There is no second copy of the colours to drift.
 */
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { type SchemeName, THEME_IDS, themeManifest, type ThemeId } from './manifest';

const styles = resolve(__dirname, '../styles');
const SETTINGS = ['00-settings/colors.css', '00-settings/typography.css', '00-settings/roles.css'];

interface Declarations {
	plain: Map<string, string>;
	guarded: Map<string, string>;
}

/** Declarations per selector, split by whether they sit inside `@supports (color: light-dark(…))`. */
const parse = (css: string, into: Map<string, Declarations>) => {
	const source = css.replace(/\/\*[\s\S]*?\*\//g, '');
	const walk = (text: string, guarded: boolean) => {
		let i = 0;
		while (i < text.length) {
			const open = text.indexOf('{', i);
			if (open === -1) return;
			const prelude = text.slice(i, open).trim();
			let depth = 1;
			let j = open + 1;
			while (depth && j < text.length) {
				if (text[j] === '{') depth++;
				if (text[j] === '}') depth--;
				j++;
			}
			const body = text.slice(open + 1, j - 1);
			if (prelude.startsWith('@supports')) {
				walk(body, guarded || prelude.includes('light-dark'));
			} else if (!prelude.startsWith('@')) {
				for (const selector of prelude.split(',').map((s) => s.trim().replace(/"/g, "'"))) {
					const entry = into.get(selector) ?? { plain: new Map(), guarded: new Map() };
					for (const m of body.matchAll(/(--[\w-]+)\s*:\s*([^;]+);/g)) {
						(guarded ? entry.guarded : entry.plain).set(m[1], m[2].trim());
					}
					into.set(selector, entry);
				}
			}
			i = j;
		}
	};
	walk(source, false);
};

const rules = new Map<string, Declarations>();
for (const file of SETTINGS) parse(readFileSync(resolve(styles, file), 'utf8'), rules);
for (const id of THEME_IDS) {
	const file = resolve(styles, `themes/${id}.css`);
	if (existsSync(file)) parse(readFileSync(file, 'utf8'), rules);
}

/** The declarations in force for a theme, in cascade order (later wins). */
const declarationsFor = (theme: ThemeId) => {
	const merged = new Map<string, string>();
	const add = (selector: string, guarded: boolean) => {
		const entry = rules.get(selector);
		if (entry) (guarded ? entry.guarded : entry.plain).forEach((value, name) => merged.set(name, value));
	};
	add(':root', false);
	add(':root', true);
	add(`:root[data-theme='${theme}']`, false);
	add(`:root[data-theme='${theme}']`, true);
	return merged;
};

/** Splits `a, b` at the top-level comma. */
const splitArgs = (args: string) => {
	let depth = 0;
	for (let i = 0; i < args.length; i++) {
		if (args[i] === '(') depth++;
		if (args[i] === ')') depth--;
		if (args[i] === ',' && depth === 0) return [args.slice(0, i).trim(), args.slice(i + 1).trim()];
	}
	return [args.trim()];
};

const resolveValue = (value: string, decls: Map<string, string>, scheme: SchemeName, seen: string[] = []): string => {
	const v = value.trim();
	const fn = /^(var|light-dark)\(([\s\S]*)\)$/.exec(v);
	if (!fn) return v;
	const [first, second] = splitArgs(fn[2]);
	if (fn[1] === 'light-dark') return resolveValue(scheme === 'light' ? first : second, decls, scheme, seen);
	if (seen.includes(first)) throw new Error(`var() cycle: ${[...seen, first].join(' -> ')}`);
	const next = decls.get(first) ?? second;
	if (next === undefined) throw new Error(`${first} is not defined`);
	return resolveValue(next, decls, scheme, [...seen, first]);
};

const luminance = (hex: string) => {
	const h = hex.replace('#', '');
	const full = h.length === 3 ? [...h].map((c) => c + c).join('') : h.slice(0, 6);
	const [r, g, b] = [0, 2, 4]
		.map((i) => parseInt(full.slice(i, i + 2), 16) / 255)
		.map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
	return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

const ratio = (a: string, b: string) => {
	const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
	return (hi + 0.05) / (lo + 0.05);
};

const SURFACES = ['--color-surface', '--color-surface-raised', '--color-surface-sunken'];
const TEXT = 4.5;
const UI = 3;

/** [foreground, background, minimum ratio]. */
const PAIRS: [string, string, number][] = [
	...[
		'--color-text',
		'--color-text-strong',
		'--color-text-subtle',
		'--color-text-muted',
		'--color-interactive',
		'--color-level-text',
	].flatMap((fg) => SURFACES.map((bg): [string, string, number] => [fg, bg, TEXT])),
	['--color-on-interactive', '--color-interactive', TEXT],
	['--color-on-interactive', '--color-interactive-hover', TEXT],
	['--color-text', '--color-interactive-tint', TEXT],
	['--color-interactive', '--color-interactive-tint', TEXT],
	['--color-pill-fg', '--color-pill-bg', TEXT],
	['--color-on-warning', '--color-warning', TEXT],
	['--color-on-warning', '--color-warning-hover', TEXT],
	// White text on fills that keep their colour in every scheme.
	...['--color-action-secondary', '--color-success', '--color-error', '--color-level'].map(
		(bg): [string, string, number] => ['--color-on-fill', bg, TEXT],
	),
	['--color-badge-accent-fg', '--color-badge-accent-bg', TEXT],
	['--color-success-text', '--color-success-tint', TEXT],
	['--color-warning-text', '--color-warning-tint', TEXT],
	['--color-error-text', '--color-error-tint', TEXT],
	...['--color-focus', '--color-border-strong', '--color-level', '--color-like-on'].flatMap((fg) =>
		['--color-surface', '--color-surface-raised'].map((bg): [string, string, number] => [fg, bg, UI]),
	),
];

const MATRIX = THEME_IDS.flatMap((theme) => themeManifest[theme].schemes.map((scheme) => [theme, scheme] as const));

describe.each(MATRIX)('%s × %s', (theme, scheme) => {
	const decls = declarationsFor(theme);

	it.each(PAIRS)('%s on %s ≥ %s:1', (fg, bg, min) => {
		const foreground = resolveValue(`var(${fg})`, decls, scheme);
		const background = resolveValue(`var(${bg})`, decls, scheme);

		// currentColor takes the colour of the element it sits in, which is checked as that element's role.
		if (foreground === 'currentColor') return;

		expect(background, `${bg} must resolve to a hex colour`).toMatch(/^#[0-9a-f]{3,8}$/i);
		expect(foreground, `${fg} must resolve to a hex colour`).toMatch(/^#[0-9a-f]{3,8}$/i);
		expect(ratio(foreground, background), `${fg} ${foreground} on ${bg} ${background}`).toBeGreaterThanOrEqual(min);
	});
});

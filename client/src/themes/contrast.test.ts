/**
 * Every role pair meets WCAG 2.2 AA in every theme × scheme, and in a browser without light-dark()
 * (STYLING-03, #498, AC-6 and AC-9). Every colour role in roles.css is listed in ROLE_CHECKS, and every
 * theme sets each role it does not share with kinari on purpose (AC-1).
 *
 * The values come from the CSS itself: colors.css, roles.css and src/styles/themes/*.css are parsed,
 * `var()` and `light-dark()` are resolved per theme and scheme, and each pair's contrast ratio is
 * checked. There is no second copy of the colours to drift.
 */
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { DEFAULT_THEME, type SchemeName, THEME_IDS, themeManifest, type ThemeId } from './manifest';

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

/** The colour roles: every `--color-*` that roles.css defines. */
const roleRules = new Map<string, Declarations>();
parse(readFileSync(resolve(styles, '00-settings/roles.css'), 'utf8'), roleRules);
const ROLES = [...(roleRules.get(':root')?.plain.keys() ?? [])].filter((name) => name.startsWith('--color-'));

/**
 * The declarations in force for a theme, in cascade order (later wins). `fallback` models a browser
 * without light-dark(), which skips everything inside the @supports guard.
 */
const declarationsFor = (theme: ThemeId, { fallback = false } = {}) => {
	const merged = new Map<string, string>();
	const add = (selector: string, guarded: boolean) => {
		const entry = rules.get(selector);
		if (entry) (guarded ? entry.guarded : entry.plain).forEach((value, name) => merged.set(name, value));
	};
	add(':root', false);
	if (!fallback) add(':root', true);
	add(`:root[data-theme='${theme}']`, false);
	if (!fallback) add(`:root[data-theme='${theme}']`, true);
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

type Check = { on: string[]; min: number } | { exempt: string };

const background = { exempt: 'a background: checked as the background of the roles drawn on it' };
const decorative = { exempt: 'decorative: never the only edge, cue or text' };
const disabled = { exempt: 'disabled: WCAG 1.4.3 and 1.4.11 exempt inactive controls' };
const hoverWash = { exempt: 'a translucent hover wash, never the only hover cue' };

/**
 * Every colour role in roles.css and what it is drawn on. A new role fails the first test until it is
 * listed here, with its backgrounds or the reason it is exempt.
 */
const ROLE_CHECKS: Record<string, Check> = {
	'--color-surface': background,
	'--color-surface-raised': background,
	'--color-surface-sunken': background,
	'--color-fill': background,
	'--color-fill-strong': background,
	'--color-fill-bold': { exempt: 'neutral data marks, always next to a text label with the value' },
	'--color-hover-overlay': hoverWash,
	'--color-hover-overlay-strong': hoverWash,

	'--color-text': { on: [...SURFACES, '--color-fill', '--color-interactive-tint'], min: TEXT },
	'--color-text-strong': { on: [...SURFACES, '--color-fill', '--color-interactive-tint'], min: TEXT },
	'--color-text-subtle': { on: [...SURFACES, '--color-fill'], min: TEXT },
	'--color-text-muted': { on: [...SURFACES, '--color-fill'], min: TEXT },
	'--color-text-disabled': disabled,

	'--color-border': decorative,
	'--color-border-emphasis': decorative,
	'--color-border-strong': { on: SURFACES, min: UI },

	'--color-interactive': { on: [...SURFACES, '--color-interactive-tint'], min: TEXT },
	'--color-interactive-hover': { on: [...SURFACES, '--color-interactive-tint'], min: TEXT },
	'--color-interactive-disabled': disabled,
	'--color-interactive-tint': background,
	'--color-on-interactive': { on: ['--color-interactive', '--color-interactive-hover'], min: TEXT },
	'--color-focus': { on: SURFACES, min: UI },
	'--color-focus-halo': { exempt: 'a soft ring around a field whose border turns --color-interactive' },
	'--color-interactive-border': { exempt: 'a hover edge on tiles that also change background or lift' },
	'--color-on-fill': {
		on: [
			'--color-action-secondary',
			'--color-action-secondary-hover',
			'--color-success',
			'--color-error',
			'--color-level',
		],
		min: TEXT,
	},
	'--color-on-warning': { on: ['--color-warning', '--color-warning-hover'], min: TEXT },
	'--color-action-secondary': background,
	'--color-action-secondary-hover': background,
	'--color-action-secondary-disabled': disabled,
	'--color-pill-bg': background,
	'--color-pill-fg': { on: ['--color-pill-bg'], min: TEXT },
	'--color-pill-border': decorative,

	'--color-level': { on: SURFACES, min: UI },
	'--color-level-text': { on: [...SURFACES, '--color-level-tint'], min: TEXT },
	'--color-level-glyph': { on: ['--color-surface', '--color-surface-raised'], min: UI }, // covers sit on cards and the page
	'--color-level-tint': background,
	'--color-badge-accent-bg': background,
	'--color-badge-accent-fg': { on: ['--color-badge-accent-bg'], min: TEXT },

	'--color-success-tint': background,
	'--color-warning-tint': background,
	'--color-error-tint': background,
	'--color-success-text': { on: [...SURFACES, '--color-success-tint'], min: TEXT },
	'--color-warning-text': { on: [...SURFACES, '--color-warning-tint'], min: TEXT },
	'--color-error-text': { on: [...SURFACES, '--color-error-tint'], min: TEXT },

	'--color-like-off': { on: SURFACES, min: UI },
	'--color-like-on': { on: SURFACES, min: UI },
};

/** Roles every theme takes from kinari on purpose: level and status mean the same thing everywhere. */
const SHARED_ROLES = new Set([
	'--color-on-fill',
	'--color-on-warning',
	'--color-level',
	'--color-level-text',
	'--color-level-glyph',
	'--color-level-tint',
	'--color-badge-accent-bg',
	'--color-badge-accent-fg',
	'--color-success-tint',
	'--color-warning-tint',
	'--color-error-tint',
	'--color-success-text',
	'--color-warning-text',
	'--color-error-text',
]);

/** [foreground, background, minimum ratio]. */
const PAIRS: [string, string, number][] = Object.entries(ROLE_CHECKS).flatMap(([fg, check]) =>
	'on' in check ? check.on.map((bg): [string, string, number] => [fg, bg, check.min]) : [],
);

const themeDeclarations = (theme: ThemeId) => {
	const entry = rules.get(`:root[data-theme='${theme}']`);
	return { plain: entry?.plain ?? new Map<string, string>(), guarded: entry?.guarded ?? new Map<string, string>() };
};

/** A role whose kinari value differs between light and dark. */
const changesWithScheme = (role: string) => {
	const root = roleRules.get(':root');
	const guarded = root?.guarded.get(role);
	return guarded !== undefined && guarded !== root?.plain.get(role);
};

describe('role coverage', () => {
	it('lists every colour role in roles.css, and nothing else', () => {
		expect(Object.keys(ROLE_CHECKS).sort()).toEqual([...ROLES].sort());
	});

	it.each(THEME_IDS.filter((theme) => theme !== DEFAULT_THEME))(
		'%s sets every colour role it does not share with kinari on purpose',
		(theme) => {
			const { plain, guarded } = themeDeclarations(theme);
			const missing = ROLES.filter((role) => !SHARED_ROLES.has(role) && !plain.has(role) && !guarded.has(role));
			expect(missing, `Set these in themes/${theme}.css, or add them to SHARED_ROLES`).toEqual([]);
		},
	);

	it.each(THEME_IDS.filter((theme) => themeManifest[theme].schemes.length === 1))(
		'%s sets every role that changes with the scheme as a plain value, outside the @supports guard',
		(theme) => {
			const { plain } = themeDeclarations(theme);
			const missing = ROLES.filter((role) => changesWithScheme(role) && !plain.has(role));
			expect(missing, "Without light-dark() these would show kinari's light value").toEqual([]);
		},
	);
});

const checkPair = (decls: Map<string, string>, scheme: SchemeName, fg: string, bg: string, min: number) => {
	const foreground = resolveValue(`var(${fg})`, decls, scheme);
	const backdrop = resolveValue(`var(${bg})`, decls, scheme);

	// currentColor takes the colour of the element it sits in, which is checked as that element's role.
	if (foreground === 'currentColor') return;

	expect(backdrop, `${bg} must resolve to a hex colour`).toMatch(/^#[0-9a-f]{3,8}$/i);
	expect(foreground, `${fg} must resolve to a hex colour`).toMatch(/^#[0-9a-f]{3,8}$/i);
	expect(ratio(foreground, backdrop), `${fg} ${foreground} on ${bg} ${backdrop}`).toBeGreaterThanOrEqual(min);
};

const MATRIX = THEME_IDS.flatMap((theme) => themeManifest[theme].schemes.map((scheme) => [theme, scheme] as const));

describe.each(MATRIX)('%s × %s', (theme, scheme) => {
	const decls = declarationsFor(theme);

	it.each(PAIRS)('%s on %s ≥ %s:1', (fg, bg, min) => checkPair(decls, scheme, fg, bg, min));
});

/** A browser without light-dark(): a two-scheme theme shows kinari light, a single-scheme theme its own scheme. */
const FALLBACK = THEME_IDS.map((theme) => {
	const { schemes } = themeManifest[theme];
	return [theme, schemes.length === 1 ? schemes[0] : ('light' as SchemeName)] as const;
});

describe.each(FALLBACK)('%s without light-dark() (%s)', (theme, scheme) => {
	const decls = declarationsFor(theme, { fallback: true });

	it.each(PAIRS)('%s on %s ≥ %s:1', (fg, bg, min) => checkPair(decls, scheme, fg, bg, min));
});

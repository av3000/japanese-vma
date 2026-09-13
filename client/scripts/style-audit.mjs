#!/usr/bin/env node
/**
 * Style audit / ratchet for the native-CSS exit (STYLING-01).
 *
 * Counts, under `src/`:
 *   - imports of react-bootstrap, react-router-bootstrap, bootstrap
 *   - imports of Tailwind tooling (tailwind-merge, class-variance-authority, tw-animate-css)
 *   - Bootstrap-style utility class tokens inside className strings
 *   - Tailwind-style utility class tokens inside className strings
 *   - .scss files
 *
 * Usage:
 *   node scripts/style-audit.mjs            # print report
 *   node scripts/style-audit.mjs --tokens   # print every offending token with its count
 *   node scripts/style-audit.mjs --files    # print per-file counts
 *   node scripts/style-audit.mjs --write    # write current counts to style-budget.json
 *   node scripts/style-audit.mjs --check    # exit 1 if any count exceeds style-budget.json
 */
import { readdirSync, readFileSync, statSync, writeFileSync, existsSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const srcDir = join(root, 'src');
const budgetPath = join(root, 'style-budget.json');
const args = new Set(process.argv.slice(2));

const IGNORED_DIRS = new Set(['generated', 'font-awesome', 'node_modules']);

const RESTRICTED_IMPORTS = /from\s+['"](react-bootstrap|react-router-bootstrap|bootstrap)(\/[^'"]*)?['"]/g;
const TAILWIND_IMPORTS = /from\s+['"](tailwind-merge|class-variance-authority|tw-animate-css|tailwindcss)(\/[^'"]*)?['"]/g;

// Bootstrap 4 utility and component class names that the CDN stylesheet used to provide.
const BOOTSTRAP_TOKEN = new RegExp(
	'^(' +
		[
			'container(-fluid)?',
			'row',
			'no-gutters',
			'col(-(xs|sm|md|lg|xl))?(-([1-9]|1[0-2]|auto))?',
			'offset(-(sm|md|lg|xl))?-([1-9]|1[0-2])',
			'd(-(sm|md|lg|xl))?-(none|inline|inline-block|block|flex|inline-flex|table|table-cell)',
			'flex(-(sm|md|lg|xl))?-(row|column|row-reverse|column-reverse|wrap|nowrap|fill|grow-[01]|shrink-[01])',
			'justify-content(-(sm|md|lg|xl))?(-(start|end|center|between|around))?',
			'align-(items|self|content)(-(sm|md|lg|xl))?-(start|end|center|baseline|stretch)',
			'[mp][tblrxy]?(-(sm|md|lg|xl))?-([0-5]|auto|n[1-5])',
			'w-(25|50|75|100|auto)',
			'h-(25|50|75|100|auto)',
			'mw-100',
			'mh-100',
			'text-(left|right|center|justify|nowrap|truncate|lowercase|uppercase|capitalize|muted|primary|secondary|success|danger|warning|info|light|dark|body|white|black-50|white-50|hide|break|decoration-none|reset)',
			'text-(sm|md|lg|xl)-(left|right|center)',
			'font-weight-(bold|bolder|normal|light|lighter)',
			'font-italic',
			'small',
			'lead',
			'display-[1-4]',
			'blockquote(-footer)?',
			'bg-(primary|secondary|success|danger|warning|info|light|dark|white|transparent)',
			'border(-(top|right|bottom|left))?(-0)?',
			'border-(primary|secondary|success|danger|warning|info|light|dark|white|gray)',
			'rounded(-(top|right|bottom|left|circle|pill|0|sm|lg))?',
			'shadow(-(none|sm|lg))?',
			'float-(left|right|none)',
			'position-(static|relative|absolute|fixed|sticky)',
			'fixed-(top|bottom)',
			'sticky-top',
			'sr-only(-focusable)?',
			'invisible',
			'visible',
			'clearfix',
			'img-(fluid|thumbnail)',
			'btn(-(primary|secondary|success|danger|warning|info|light|dark|link|block|sm|lg|group|toolbar))?',
			'btn-outline-(primary|secondary|success|danger|warning|info|light|dark)',
			'form-(group|control|control-sm|control-lg|control-plaintext|control-file|control-range|check|check-input|check-label|check-inline|text|inline|row)',
			'input-group(-(prepend|append|text|sm|lg))?',
			'custom-(select|control|control-input|control-label|checkbox|radio|switch|file)',
			'list-group(-(item|item-action|flush|horizontal))?',
			'list-group-item-(primary|secondary|success|danger|warning|info|light|dark)',
			'list-(unstyled|inline|inline-item)',
			'card(-(body|title|subtitle|text|link|header|footer|img|img-top|img-bottom|img-overlay|deck|group|columns))?',
			'alert(-(primary|secondary|success|danger|warning|info|light|dark|link|heading|dismissible))?',
			'badge(-(primary|secondary|success|danger|warning|info|light|dark|pill))?',
			'table(-(striped|bordered|borderless|hover|sm|responsive|dark|light|active|primary|secondary|success|danger|warning|info))?',
			'thead-(dark|light)',
			'nav(-(link|item|tabs|pills|fill|justified))?',
			'navbar(-(brand|nav|toggler|toggler-icon|collapse|text|expand|expand-sm|expand-md|expand-lg|expand-xl|light|dark))?',
			'dropdown(-(menu|item|toggle|divider|header))?',
			'collapse',
			'close',
			'modal(-(dialog|content|header|title|body|footer|backdrop|open))?',
			'spinner-(border|grow)(-sm)?',
			'progress(-bar)?',
			'jumbotron',
			'pagination',
			'page-(item|link)',
			'breadcrumb(-item)?',
			'embed-responsive(-item)?',
			'stretched-link',
			'overflow-(auto|hidden)',
			'order-(first|last|[0-9]|1[0-2])',
			'align-(baseline|top|middle|bottom|text-top|text-bottom)',
		].join('|') +
		')$',
);

// Tailwind utility shapes. Deliberately narrow: only patterns that cannot be a Bootstrap 4 class.
const TAILWIND_TOKEN =
	/^(!?-?)([a-z]+:)*(inline-flex|inline-block|items-(start|end|center|baseline|stretch)|justify-(start|end|center|between|around|evenly)|self-\w+|(rounded|border|ring|outline|shadow|opacity|z|gap|space-[xy]|inset|top|left|right|bottom|w|h|min-w|min-h|max-w|max-h|size|p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|text|font|leading|tracking|bg|fill|stroke|duration|delay|ease|animate|origin|translate|scale|rotate|grid-cols|grid-rows|col-span|row-span|basis|order|aspect|columns|break|whitespace|overflow|object|cursor|select|pointer-events|scroll|snap|transition|will-change|content|list|decoration|underline-offset|indent|align|hyphens|caret|accent|appearance|resize|touch|divide|placeholder|backdrop|blur|brightness|contrast|drop-shadow|grayscale|invert|saturate|sepia|mix-blend|bg-blend|isolate|sr-only|not-sr-only)-(\[[^\]]+\]|[a-z0-9./%()-]+)|(flex|grid|hidden|block|inline|relative|absolute|fixed|sticky|static|truncate|uppercase|lowercase|capitalize|italic|underline|antialiased|shrink|grow|shrink-0|grow-0|w-fit|w-full|h-full|rounded-full|rounded-lg|rounded-md|outline-hidden|overflow-hidden|whitespace-nowrap))$/;

const BOOTSTRAP_ONLY_LOOKING_TAILWIND = /^(rounded-full|rounded-lg|rounded-md|whitespace-nowrap|overflow-hidden|w-full|h-full|w-fit|inline-flex|items-|justify-|gap-|ring-|shrink|grow|size-|text-\[|p-\d\.\d|[wh]-\d\.\d|[wh]-\d\d)/;

const CLASS_STRING_SOURCES = [
	/className=\{?\s*["'`]([^"'`]*)["'`]/g,
	/class(?:Names|names)?\(([^()]*(?:\([^()]*\)[^()]*)*)\)/g,
	/\bcn\(([^()]*(?:\([^()]*\)[^()]*)*)\)/g,
	/\bcva\(\s*(["'`][^"'`]*["'`])/g,
];

function walk(dir, out = []) {
	for (const entry of readdirSync(dir)) {
		if (IGNORED_DIRS.has(entry)) continue;
		const full = join(dir, entry);
		const st = statSync(full);
		if (st.isDirectory()) walk(full, out);
		else out.push(full);
	}
	return out;
}

function extractClassStrings(source) {
	const strings = [];
	for (const re of CLASS_STRING_SOURCES) {
		re.lastIndex = 0;
		let m;
		while ((m = re.exec(source))) {
			const chunk = m[1];
			// Pull string literals out of call arguments; plain attribute values arrive as-is.
			const literals = chunk.match(/["'`]([^"'`]*)["'`]/g);
			if (literals) {
				for (const lit of literals) strings.push(lit.slice(1, -1));
			} else {
				strings.push(chunk);
			}
		}
	}
	return strings;
}

function classify(token) {
	if (BOOTSTRAP_TOKEN.test(token)) {
		// Tokens like `mt-3`/`p-0` are legal in both systems; treat as Bootstrap outside components/ui.
		return 'bootstrap';
	}
	if (TAILWIND_TOKEN.test(token) || BOOTSTRAP_ONLY_LOOKING_TAILWIND.test(token)) return 'tailwind';
	return null;
}

const files = walk(srcDir);
const tsxFiles = files.filter((f) => /\.(tsx|ts|jsx|js)$/.test(f));
const scssFiles = files.filter((f) => f.endsWith('.scss'));

const perFile = new Map();
const tokenCounts = { bootstrap: new Map(), tailwind: new Map() };
const totals = {
	reactBootstrapImports: 0,
	tailwindImports: 0,
	bootstrapClassTokens: 0,
	tailwindClassTokens: 0,
	scssFiles: scssFiles.length,
};

for (const file of tsxFiles) {
	const source = readFileSync(file, 'utf8');
	const rel = relative(root, file).replaceAll('\\', '/');
	const entry = { reactBootstrapImports: 0, tailwindImports: 0, bootstrap: 0, tailwind: 0 };

	entry.reactBootstrapImports = (source.match(RESTRICTED_IMPORTS) ?? []).length;
	entry.tailwindImports = (source.match(TAILWIND_IMPORTS) ?? []).length;

	for (const str of extractClassStrings(source)) {
		for (const raw of str.split(/\s+/)) {
			const token = raw.trim();
			if (!token) continue;
			const kind = classify(token);
			if (!kind) continue;
			entry[kind] += 1;
			tokenCounts[kind].set(token, (tokenCounts[kind].get(token) ?? 0) + 1);
		}
	}

	totals.reactBootstrapImports += entry.reactBootstrapImports;
	totals.tailwindImports += entry.tailwindImports;
	totals.bootstrapClassTokens += entry.bootstrap;
	totals.tailwindClassTokens += entry.tailwind;

	if (entry.reactBootstrapImports || entry.tailwindImports || entry.bootstrap || entry.tailwind) {
		perFile.set(rel, entry);
	}
}

function printReport() {
	console.log('Style audit (src/)');
	console.table(totals);
	console.log(`Files with findings: ${perFile.size} / ${tsxFiles.length}`);
}

function printTokens() {
	for (const kind of ['bootstrap', 'tailwind']) {
		console.log(`\n${kind} tokens (${tokenCounts[kind].size} unique):`);
		for (const [token, count] of [...tokenCounts[kind].entries()].sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]))) {
			console.log(`${String(count).padStart(5)}  ${token}`);
		}
	}
}

function printFiles() {
	const rows = [...perFile.entries()]
		.map(([file, e]) => ({ file, ...e }))
		.sort((a, b) => b.bootstrap + b.tailwind - (a.bootstrap + a.tailwind));
	console.table(rows);
}

printReport();
if (args.has('--tokens')) printTokens();
if (args.has('--files')) printFiles();

if (args.has('--write')) {
	writeFileSync(budgetPath, JSON.stringify(totals, null, 2) + '\n');
	console.log(`Wrote ${relative(root, budgetPath)}`);
}

if (args.has('--check')) {
	if (!existsSync(budgetPath)) {
		console.error('style-budget.json is missing; run with --write first.');
		process.exit(1);
	}
	const budget = JSON.parse(readFileSync(budgetPath, 'utf8'));
	const overruns = Object.entries(totals).filter(([key, value]) => value > (budget[key] ?? 0));
	if (overruns.length) {
		console.error('\nStyle budget exceeded:');
		for (const [key, value] of overruns) {
			console.error(`  ${key}: ${value} > ${budget[key] ?? 0}`);
		}
		console.error('\nMigrate the new usage to CSS Modules / shared primitives instead of Bootstrap or Tailwind classes.');
		process.exit(1);
	}
	const slack = Object.entries(totals).filter(([key, value]) => value < (budget[key] ?? 0));
	if (slack.length) {
		console.log('\nBudget can be lowered (run --write):');
		for (const [key, value] of slack) console.log(`  ${key}: ${value} < ${budget[key]}`);
	}
	console.log('\nStyle budget OK.');
}

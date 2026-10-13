#!/usr/bin/env node
/**
 * Story coverage audit / ratchet (#578): every component module and route has a story, or an entry
 * in story-coverage.json saying why not.
 *
 * A module is, under src/components and src/routes:
 *   - a directory whose only non-test `.tsx` file is its component: covered when a `*.stories.tsx`
 *     sits in that directory;
 *   - each `.tsx` file in a directory that holds several: covered when a `*.stories.tsx` in that
 *     directory imports it, directly or through the directory's barrel (`index.ts` of re-exports).
 * Hooks, `.ts` helpers, barrels, fixtures, tests and stories are not modules.
 *
 * story-coverage.json maps uncovered modules to a reason:
 *   "covered-by: <story file>"  rendered only through another story
 *   "dead: #<issue>"            awaiting removal
 *   "pending: #<issue>"         another open issue is about to change or delete it
 *   "not-visual"                route tables, lazy-route plumbing, test helpers
 *   "todo: #<issue>"            not storied yet; the issue owns it
 *
 * Usage:
 *   node scripts/story-audit.mjs           # report uncovered modules
 *   node scripts/story-audit.mjs --check   # exit 1 on an unlisted gap or a stale allowlist entry
 *   node scripts/story-audit.mjs --write   # rewrite the allowlist: keep reasons, add new gaps as "todo"
 */
import { existsSync, readdirSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const allowlistPath = join(root, "story-coverage.json");
const scanned = ["src/components", "src/routes"];
const args = new Set(process.argv.slice(2));

const toPosix = (path) => path.split("\\").join("/");
const isComponentFile = (name) => name.endsWith(".tsx") && !/\.(test|stories)\.tsx$/.test(name);
const isStory = (name) => name.endsWith(".stories.tsx");
const RELATIVE_IMPORT = /from\s+['"](\.[^'"]*)['"]/g;
const BARREL_LINE = /^export (\*|\{[^}]*\}|type \{[^}]*\}) from /;

/** What a file imports relatively, as absolute posix paths without extension or `/index`. */
const importsOf = (file) =>
  [...readFileSync(file, "utf8").matchAll(RELATIVE_IMPORT)].map((match) =>
    toPosix(resolve(dirname(file), match[1])).replace(/\/(index)?$/, ""),
  );

/** The directory's `index.ts(x)` when it only re-exports, otherwise null. */
const barrelOf = (dir) => {
  for (const name of ["index.ts", "index.tsx"]) {
    const file = join(dir, name);
    if (!existsSync(file)) continue;

    const lines = readFileSync(file, "utf8")
      .split("\n")
      .map((line) => line.trim())
      .filter((line) => line && !line.startsWith("//"));

    if (lines.length > 0 && lines.every((line) => BARREL_LINE.test(line))) return file;
  }

  return null;
};

const modules = new Map(); // module path -> covered

const walk = (dir) => {
  const entries = readdirSync(dir, { withFileTypes: true });
  const barrel = barrelOf(dir);
  const components = entries
    .filter((entry) => entry.isFile() && isComponentFile(entry.name))
    .map((entry) => entry.name)
    .filter((name) => join(dir, name) !== barrel);
  const stories = entries.filter((entry) => entry.isFile() && isStory(entry.name)).map((entry) => join(dir, entry.name));
  const rel = toPosix(relative(root, dir));

  if (components.length === 1) {
    modules.set(rel, stories.length > 0);
  } else if (components.length > 1) {
    const self = toPosix(dir);
    const imported = new Set(stories.flatMap(importsOf));

    if (barrel && imported.has(self)) {
      for (const target of importsOf(barrel)) imported.add(target);
    }

    for (const name of components) {
      const base = name.replace(/\.tsx$/, "");
      modules.set(`${rel}/${name}`, imported.has(`${self}/${base}`) || (base === "index" && imported.has(self)));
    }
  }

  for (const entry of entries) {
    if (entry.isDirectory()) walk(join(dir, entry.name));
  }
};

for (const dir of scanned) walk(join(root, dir));

const allowlist = existsSync(allowlistPath) ? JSON.parse(readFileSync(allowlistPath, "utf8")) : {};
const uncovered = [...modules]
  .filter(([, covered]) => !covered)
  .map(([path]) => path)
  .sort();
const unlisted = uncovered.filter((path) => !(path in allowlist));
const stale = Object.keys(allowlist).filter((path) => !modules.has(path) || modules.get(path));

const byReason = uncovered.reduce((counts, path) => {
  const kind = path in allowlist ? allowlist[path].split(":")[0] : "unlisted";
  counts[kind] = (counts[kind] ?? 0) + 1;
  return counts;
}, {});

console.log("Story audit (src/components, src/routes)");
console.log(`Modules: ${modules.size}, storied: ${modules.size - uncovered.length}, uncovered: ${uncovered.length}`);
console.log(`Uncovered by reason: ${JSON.stringify(byReason)}`);

if (!args.has("--check") && !args.has("--write")) {
  for (const path of uncovered) console.log(`  ${path}  ${allowlist[path] ?? "(not in story-coverage.json)"}`);
}

if (args.has("--write")) {
  const next = Object.fromEntries(uncovered.map((path) => [path, allowlist[path] ?? "todo"]));
  writeFileSync(allowlistPath, JSON.stringify(next, null, 2) + "\n");
  console.log(`Wrote ${relative(root, allowlistPath)} (${uncovered.length} entries)`);
}

if (args.has("--check")) {
  if (unlisted.length > 0) {
    console.error("\nModules without a story and not in story-coverage.json:");
    for (const path of unlisted) console.error(`  ${path}`);
    console.error("Add a story next to the module, or an allowlist entry with a reason.");
  }

  if (stale.length > 0) {
    console.error("\nStale story-coverage.json entries (now storied, or gone); remove them:");
    for (const path of stale) console.error(`  ${path}`);
  }

  if (unlisted.length > 0 || stale.length > 0) process.exit(1);
  console.log("\nStory coverage OK.");
}

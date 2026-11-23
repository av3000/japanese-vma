#!/usr/bin/env node
// Lint the agent skills in .claude/skills/.
//
//   node scripts/lint-agent-skills.mjs
//
// Each skill must have:
//   - frontmatter whose `name` matches the folder name
//   - a `description` of at most 1024 characters
//   - a SKILL.md of at most 500 lines
//   - working relative links: Markdown links, and backticked paths under
//     references/, rules/, templates/, scripts/, assets/ or agents/
//
// And the workflow guide (docs/agents/workflow.md) must list every skill in
// its "skills at a glance" table, and every row whose source is "repo" must
// name skills that exist.
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const skillsDir = join(root, '.claude', 'skills');

const MAX_DESCRIPTION = 1024;
const MAX_LINES = 500;
const BUNDLED_DIRS = ['references', 'rules', 'templates', 'scripts', 'assets', 'agents'];

const errors = [];
const fail = (skill, message) => errors.push(`${skill}: ${message}`);

function readFrontmatter(text) {
	const match = text.match(/^---\r?\n([\s\S]*?)\r?\n---/);
	if (!match) return null;

	const fields = {};
	let key = null;
	for (const line of match[1].split(/\r?\n/)) {
		const pair = line.match(/^([A-Za-z_-]+):\s*(.*)$/);
		if (pair) {
			key = pair[1];
			fields[key] = pair[2];
		} else if (key) {
			fields[key] += ` ${line.trim()}`;
		}
	}
	for (const [name, value] of Object.entries(fields)) {
		fields[name] = value
			.trim()
			.replace(/^[>|][+-]?\s*/, '')
			.replace(/^(['"])([\s\S]*)\1$/, '$2')
			.trim();
	}
	return fields;
}

function linkedPaths(text) {
	const paths = [];
	for (const [, target] of text.matchAll(/\]\(([^)\s]+)\)/g)) {
		if (/^(https?:|mailto:|#)/.test(target)) continue;
		paths.push(target.split('#')[0]);
	}
	const bundled = new RegExp('`((?:' + BUNDLED_DIRS.join('|') + ')/[^`\\s*]+)`', 'g');
	for (const [, target] of text.matchAll(bundled)) paths.push(target);
	return paths;
}

const skills = readdirSync(skillsDir).filter((entry) => statSync(join(skillsDir, entry)).isDirectory());

for (const skill of skills) {
	const skillDir = join(skillsDir, skill);
	const skillFile = join(skillDir, 'SKILL.md');
	if (!existsSync(skillFile)) {
		fail(skill, 'missing SKILL.md');
		continue;
	}

	const text = readFileSync(skillFile, 'utf8');
	const frontmatter = readFrontmatter(text);
	if (!frontmatter) {
		fail(skill, 'SKILL.md has no frontmatter block');
		continue;
	}

	if (frontmatter.name !== skill) {
		fail(skill, `frontmatter name "${frontmatter.name ?? ''}" does not match the folder name`);
	}

	const description = frontmatter.description ?? '';
	if (!description) {
		fail(skill, 'frontmatter description is missing');
	} else if (description.length > MAX_DESCRIPTION) {
		fail(skill, `description is ${description.length} characters (max ${MAX_DESCRIPTION})`);
	}

	const lines = text.split(/\r?\n/).length;
	if (lines > MAX_LINES) {
		fail(skill, `SKILL.md is ${lines} lines (max ${MAX_LINES})`);
	}

	for (const target of new Set(linkedPaths(text))) {
		if (!existsSync(join(skillDir, target))) {
			fail(skill, `links to ${target}, which does not exist`);
		}
	}
}

const guideFile = join(root, 'docs', 'agents', 'workflow.md');
const guide = 'docs/agents/workflow.md';
if (!existsSync(guideFile)) {
	errors.push(`${guide}: missing`);
} else {
	const lines = readFileSync(guideFile, 'utf8').split(/\r?\n/);
	const start = lines.findIndex((line) => /^##\s+The skills at a glance/i.test(line));
	const rows = [];
	if (start === -1) {
		errors.push(`${guide}: no "## The skills at a glance" section`);
	} else {
		for (const line of lines.slice(start + 1)) {
			if (/^#{1,6}\s/.test(line)) break;
			if (!line.startsWith('|') || /^\|\s*-/.test(line) || /^\|\s*Skill\s*\|/i.test(line)) continue;
			const [names, source] = line.split('|').slice(1).map((cell) => cell.trim());
			rows.push({ names: [...names.matchAll(/`\/?([^`]+)`/g)].map(([, name]) => name), source });
		}
	}

	const listed = new Set(rows.flatMap((row) => row.names));
	for (const skill of skills) {
		if (!listed.has(skill)) errors.push(`${skill}: not listed in the ${guide} skills table`);
	}
	for (const row of rows.filter((row) => /^repo$/i.test(row.source))) {
		for (const name of row.names) {
			if (!skills.includes(name)) errors.push(`${guide}: names repo skill "${name}", which does not exist in .claude/skills`);
		}
	}
}

if (errors.length > 0) {
	console.error(`Agent skill lint failed:\n${errors.map((error) => `  - ${error}`).join('\n')}`);
	process.exit(1);
}

console.log(`Agent skill lint passed (${skills.length} skills).`);

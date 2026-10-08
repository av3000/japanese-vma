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

if (errors.length > 0) {
	console.error(`Agent skill lint failed:\n${errors.map((error) => `  - ${error}`).join('\n')}`);
	process.exit(1);
}

console.log(`Agent skill lint passed (${skills.length} skills).`);

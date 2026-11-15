#!/usr/bin/env node
// PreToolUse guard for Bash: agents may squash-merge story PRs into an epic
// branch, never a PR into develop or master.
//
// .claude/settings.json allows `gh pr merge` so start-epic-workflow can run
// unattended, but a permission rule cannot see a PR's base branch. This hook
// can: it asks GitHub for the base and denies the merge when it is protected.
// A GraphQL merge mutation is always denied, so the check cannot be bypassed.
// When the base cannot be read, it denies (fail closed).
//
// Input: the hook payload on stdin. Output: a deny decision, or nothing.
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';

const PROTECTED = new Set(['develop', 'master', 'main']);
const REPO = 'av3000/japanese-vma';
const GH = ['C:/Program Files/GitHub CLI/gh.exe', '/c/Program Files/GitHub CLI/gh.exe'].find((path) => existsSync(path)) ?? 'gh';

const deny = (reason) => {
	process.stdout.write(
		JSON.stringify({
			hookSpecificOutput: {
				hookEventName: 'PreToolUse',
				permissionDecision: 'deny',
				permissionDecisionReason: `${reason} Merging into develop or master is the maintainer's job.`,
			},
		}),
	);
	process.exit(0);
};

let command = '';
try {
	command = JSON.parse(readFileSync(0, 'utf8'))?.tool_input?.command ?? '';
} catch {
	process.exit(0);
}

if (/mergePullRequest|enablePullRequestAutoMerge|mergeBranch/.test(command)) {
	deny('A pull request merge through the GraphQL API is not allowed for agents; use `gh pr merge` on a story PR.');
}

const merge = command.match(/\bpr\s+merge\b([^;&|\n]*)/);
if (!merge) process.exit(0);

const number = merge[1].match(/(?:^|\s)#?(\d+)(?=\s|$)/)?.[1];
if (!number) deny('`gh pr merge` needs an explicit PR number so its base branch can be checked.');

let base = '';
try {
	base = execFileSync(GH, ['pr', 'view', number, '--repo', REPO, '--json', 'baseRefName', '--jq', '.baseRefName'], {
		encoding: 'utf8',
		stdio: ['ignore', 'pipe', 'ignore'],
		timeout: 20000,
	}).trim();
} catch {
	deny(`Could not read the base branch of PR #${number}, so the merge is blocked.`);
}

if (PROTECTED.has(base)) deny(`PR #${number} targets ${base}.`);

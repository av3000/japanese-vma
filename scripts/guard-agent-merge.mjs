#!/usr/bin/env node
// PreToolUse guard for Bash: agents may squash-merge story PRs into an epic
// branch, never a PR into develop or master.
//
// .claude/settings.json allows `gh pr merge` so start-epic-workflow can run
// unattended, but a permission rule cannot see a PR's base branch. This hook
// can: it asks GitHub for the base of every PR the command merges and denies
// the command when one is protected. It also denies the other ways to merge
// that skip that check: a GraphQL merge mutation, a GraphQL query read from a
// file, the REST merge endpoints, and changing a PR's base with `pr edit`.
//
// It fails closed: a merge with no PR number, a base it cannot read, a payload
// it cannot parse, or a crash all deny when the command mentions "merge".
// Wire it in .claude/settings.json on every Bash call, as
// `node "$CLAUDE_PROJECT_DIR/scripts/guard-agent-merge.mjs" || exit 2`, so a
// hook that cannot run blocks the call instead of letting it through.
//
// Input: the hook payload on stdin. Output: a deny decision, or nothing.
// Tests: node --test scripts/guard-agent-merge.test.mjs
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

const PROTECTED = new Set(['develop', 'master', 'main']);
const REPO = 'av3000/japanese-vma';

// `gh pr merge` flags that take a value; every other flag is a switch.
const MERGE_VALUE_FLAGS = new Set([
	'--subject', '-t', '--body', '-b', '--body-file', '-F', '--author-email', '-A',
	'--match-head-commit', '--repo', '-R',
]);
const SEPARATORS = new Set([';', '&', '|', '\n']);

// Split a shell command into segments of words, honouring quotes, so a PR
// number inside `--subject "fix 668"` or a command after `&&` is seen for what
// it is. Not a full shell parser: anything it cannot see as a plain `pr merge`
// is caught by the raw-text fallback in decide().
export function segments(command) {
	const result = [];
	let words = [];
	let word = '';
	let inWord = false;
	let quote = '';
	const endWord = () => {
		if (inWord) words.push(word);
		word = '';
		inWord = false;
	};
	for (const char of command) {
		if (quote) {
			if (char === quote) quote = '';
			else word += char;
		} else if (char === '"' || char === "'") {
			quote = char;
			inWord = true;
		} else if (SEPARATORS.has(char)) {
			endWord();
			if (words.length) result.push(words);
			words = [];
		} else if (/\s/.test(char)) {
			endWord();
		} else {
			word += char;
			inWord = true;
		}
	}
	endWord();
	if (words.length) result.push(words);
	return result;
}

// The PR argument of one `pr merge`: the first word after `merge` that is not
// a flag or a flag's value. Returns undefined when there is none.
function mergeTarget(words) {
	for (let i = 0; i < words.length; i += 1) {
		const word = words[i];
		if (MERGE_VALUE_FLAGS.has(word)) i += 1;
		else if (!word.startsWith('-')) return word;
	}
	return undefined;
}

// Returns the reason to deny `command`, or null to let it run. `baseOf(n)`
// returns the base branch of PR n and throws when it cannot be read.
export function decide(command, baseOf) {
	if (/\bapi\s+graphql\b/.test(command)) {
		if (/mergePullRequest|enablePullRequestAutoMerge|mergeBranch/.test(command)) {
			return 'A merge through the GraphQL API is not allowed for agents; use `gh pr merge <number>` on a story PR.';
		}
		if (/\bquery=@/.test(command) || /(^|\s)--input\b/.test(command)) {
			return 'A GraphQL query read from a file or stdin cannot be checked; pass the query inline.';
		}
	}

	if (/pulls\/[^\s/'"]+\/merge(?![\w-])|repos\/[^\s/'"]+\/[^\s/'"]+\/merges(?![\w-])/.test(command)) {
		return 'The REST merge endpoints are not allowed for agents; use `gh pr merge <number>` on a story PR.';
	}

	let merges = 0;
	for (const words of segments(command)) {
		for (let i = 0; i + 1 < words.length; i += 1) {
			if (words[i] !== 'pr') continue;
			const rest = words.slice(i + 2);
			if (words[i + 1] === 'edit' && rest.some((word) => /^(--base|-B)(=|$)/.test(word))) {
				return 'Changing a PR\'s base branch is not allowed for agents: the merge guard checks the base.';
			}
			if (words[i + 1] !== 'merge') continue;
			merges += 1;
			const target = mergeTarget(rest);
			const number = target?.match(/^#?(\d+)$/)?.[1];
			if (!number) {
				return '`gh pr merge` needs the PR number as its argument (not a URL or a branch) so its base can be checked.';
			}
			let base;
			try {
				base = baseOf(number);
			} catch {
				return `Could not read the base branch of PR #${number}, so the merge is blocked.`;
			}
			if (PROTECTED.has(base)) return `PR #${number} targets ${base}.`;
		}
	}

	// `pr merge` hidden in a quoted string or a subshell (`bash -c '...'`) was
	// not seen as a command above, so its PR was not checked.
	if (merges === 0 && /\bpr\s+merge\b/.test(command)) {
		return '`gh pr merge` must be run as a plain command, not inside quotes or a subshell, so its base can be checked.';
	}
	return null;
}

const ghBaseOf = (number) => {
	const gh = ['C:/Program Files/GitHub CLI/gh.exe', '/c/Program Files/GitHub CLI/gh.exe'].find((path) => existsSync(path)) ?? 'gh';
	return execFileSync(gh, ['pr', 'view', number, '--repo', REPO, '--json', 'baseRefName', '--jq', '.baseRefName'], {
		encoding: 'utf8',
		stdio: ['ignore', 'pipe', 'ignore'],
		timeout: 20000,
	}).trim();
};

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

function main() {
	let raw = '';
	try {
		raw = readFileSync(0, 'utf8');
		const command = JSON.parse(raw)?.tool_input?.command ?? '';
		const reason = decide(String(command), ghBaseOf);
		if (reason) deny(reason);
	} catch {
		if (/merge/i.test(raw)) deny('The merge guard could not read this command, so it is blocked.');
	}
	process.exit(0);
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) main();

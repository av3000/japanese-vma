#!/usr/bin/env node
// Fail a PR that links no issue, or links an issue without acceptance criteria.
//
// Environment:
//   PR_BODY            the pull request body
//   PR_AUTHOR          the pull request author's login (bots are exempt)
//   GITHUB_REPOSITORY  owner/repo
//   GITHUB_TOKEN       optional; raises the API rate limit
//
// Local run (public repo, no token needed):
//   PR_BODY="Refs #503" GITHUB_REPOSITORY=av3000/japanese-vma node scripts/check-pr-issue-link.mjs

const body = (process.env.PR_BODY ?? '').replace(/<!--[\s\S]*?-->/g, '');
const author = process.env.PR_AUTHOR ?? '';
const repository = process.env.GITHUB_REPOSITORY;
const token = process.env.GITHUB_TOKEN;

if (author.endsWith('[bot]')) {
	console.log(`Skipped: ${author} is a bot.`);
	process.exit(0);
}

if (!repository) {
	console.error('GITHUB_REPOSITORY is not set.');
	process.exit(2);
}

// "Refs #1", "Refs #1, #2", "Closes #3", "Fixes #4" ... in any case.
const keyword = /\b(?:refs?|close[sd]?|fix(?:e[sd])?|resolve[sd]?)\b[:\s]*((?:#\d+[\s,]*(?:and\s+)?)+)/gi;
const numbers = new Set();
for (const [, list] of body.matchAll(keyword)) {
	for (const [, number] of list.matchAll(/#(\d+)/g)) numbers.add(Number(number));
}

// "## Acceptance criteria" or "## Acceptance" (### from issue forms), followed by
// at least one checklist line before the next heading.
function hasAcceptanceCriteria(issueBody) {
	const lines = (issueBody ?? '').split(/\r?\n/);
	const start = lines.findIndex((line) => /^#{2,3}\s+acceptance(\s+criteria)?\s*$/i.test(line.trim()));
	if (start === -1) return false;
	for (const line of lines.slice(start + 1)) {
		if (/^#{1,6}\s/.test(line.trim())) break;
		if (/^\s*[-*]\s+\[[ xX]\]\s+\S/.test(line)) return true;
	}
	return false;
}

async function fetchIssue(number) {
	const headers = { Accept: 'application/vnd.github+json', 'X-GitHub-Api-Version': '2022-11-28' };
	if (token) headers.Authorization = `Bearer ${token}`;
	const response = await fetch(`https://api.github.com/repos/${repository}/issues/${number}`, { headers });
	if (response.status === 404) return null;
	if (!response.ok) throw new Error(`GitHub API returned ${response.status} for #${number}`);
	return response.json();
}

const problems = [];
let linkedIssues = 0;

for (const number of numbers) {
	const issue = await fetchIssue(number);
	if (!issue) {
		problems.push(`#${number} does not exist in ${repository}.`);
		continue;
	}
	if (issue.pull_request) continue; // a PR reference, not an issue
	linkedIssues += 1;
	if (hasAcceptanceCriteria(issue.body)) {
		console.log(`#${number} has acceptance criteria.`);
	} else {
		problems.push(
			`#${number} ("${issue.title}") has no acceptance-criteria checklist. Add an "## Acceptance criteria" heading followed by "- [ ]" lines.`,
		);
	}
}

if (linkedIssues === 0) {
	problems.unshift('The PR body references no issue. Add "Refs #N" for every issue this PR delivers.');
}

// Set exitCode rather than calling process.exit(): exiting while fetch's
// sockets are still closing crashes Node on Windows.
if (problems.length > 0) {
	console.error(`PR issue-link check failed:\n${problems.map((problem) => `  - ${problem}`).join('\n')}`);
	process.exitCode = 1;
} else {
	console.log(`PR issue-link check passed (${linkedIssues} linked issue${linkedIssues === 1 ? '' : 's'}).`);
}

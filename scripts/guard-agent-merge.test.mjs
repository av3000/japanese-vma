// Run with: node --test scripts/guard-agent-merge.test.mjs
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { decide } from './guard-agent-merge.mjs';

const GH = "'/c/Program Files/GitHub CLI/gh.exe'";
const BASES = { 668: 'feature/issue-659-translations', 651: 'develop', 700: 'master' };
const baseOf = (number) => {
	if (!(number in BASES)) throw new Error(`no PR ${number}`);
	return BASES[number];
};
const allowed = (command) => assert.equal(decide(command, baseOf), null, command);
const denied = (command) => assert.match(decide(command, baseOf) ?? '', /./, command);

test('allows a squash merge of a story PR into an epic branch', () => {
	allowed(`${GH} pr merge 668 --repo av3000/japanese-vma --squash --subject "TRANSLATIONS-01: catalogue (#668)" --body "Refs #660"`);
	allowed(`${GH} pr merge #668 --squash`);
	allowed(`${GH} pr merge --squash --subject "fix 651 things" 668`);
});

test('denies a merge into develop or master', () => {
	denied(`${GH} pr merge 651 --squash`);
	denied(`${GH} pr merge 700`);
});

test('checks every merge in a chained command', () => {
	denied(`${GH} pr merge 668 --squash && ${GH} pr merge 651 --squash`);
	denied(`${GH} pr merge 668 --squash; ${GH} pr merge 651`);
	denied(`${GH} pr merge 668 --squash | ${GH} pr merge 651`);
});

test('reads the PR number from the positional argument, not a flag value', () => {
	denied(`${GH} pr merge --squash --subject "fix 668 things" 651`);
	denied(`${GH} pr merge --body 668 651`);
});

test('denies a merge with no number, a URL or a branch', () => {
	denied(`${GH} pr merge --squash`);
	denied(`${GH} pr merge https://github.com/av3000/japanese-vma/pull/668`);
	denied(`${GH} pr merge feature/x --squash`);
});

test('denies when the base cannot be read', () => {
	denied(`${GH} pr merge 999 --squash`);
});

test('denies a merge hidden in a subshell', () => {
	denied(`bash -c "${GH} pr merge 651 --squash"`);
});

test('denies GraphQL merges and queries read from a file', () => {
	denied(`${GH} api graphql -f query='mutation { mergePullRequest(input:{pullRequestId:"x"}) { clientMutationId } }'`);
	denied(`${GH} api graphql -f query='mutation { enablePullRequestAutoMerge(input:{}) { clientMutationId } }'`);
	denied(`${GH} api graphql -F query=@merge.graphql`);
	denied(`${GH} api graphql --input payload.json`);
});

test('allows the GraphQL calls the skills make', () => {
	allowed(`${GH} api graphql -F owner=av3000 -F name=japanese-vma -F n=659 -f query='query { repository { issue { subIssues { nodes { number } } } } }'`);
	allowed(`${GH} api graphql -F id=PRRT_1 -F body=@reply.md -f query='mutation($id:ID!, $body:String!) { addPullRequestReviewThreadReply(input:{pullRequestReviewThreadId:$id, body:$body}) { comment { url } } }'`);
});

test('denies the REST merge endpoints', () => {
	denied(`${GH} api -X PUT repos/av3000/japanese-vma/pulls/651/merge -f merge_method=squash`);
	denied(`${GH} api --method PUT /repos/av3000/japanese-vma/pulls/668/merge`);
	denied(`${GH} api -X POST repos/av3000/japanese-vma/merges -f base=develop -f head=x`);
});

test('allows the REST calls the skills make', () => {
	allowed(`${GH} api repos/av3000/japanese-vma/pulls/668/reviews --method POST --input review.json`);
	allowed(`${GH} api repos/av3000/japanese-vma/pulls/668/comments --paginate`);
});

test('denies changing a PR base', () => {
	denied(`${GH} pr edit 668 --base develop && ${GH} pr merge 668`);
	denied(`${GH} pr edit 668 -B develop`);
	denied(`${GH} pr edit 668 --base=develop`);
	allowed(`${GH} pr edit 668 --body-file body.md`);
});

test('ignores commands that only mention merging', () => {
	allowed('git status');
	allowed('grep -rn mergeBranch scripts');
	allowed('git merge origin/develop --no-edit');
	allowed(`${GH} pr view 651 --json mergedAt`);
});

const run = (stdin) =>
	spawnSync(process.execPath, [fileURLToPath(new URL('./guard-agent-merge.mjs', import.meta.url))], {
		input: stdin,
		encoding: 'utf8',
	});

test('the hook fails closed on a payload it cannot parse that mentions merge', () => {
	const result = run('{"tool_input": {"command": "gh pr merge 651"');
	assert.equal(result.status, 0);
	assert.match(result.stdout, /"permissionDecision":"deny"/);
});

test('the hook stays silent on an unparseable payload without merge, and on an allowed command', () => {
	assert.equal(run('').stdout, '');
	assert.equal(run('not json').stdout, '');
	assert.equal(run(JSON.stringify({ tool_input: { command: 'git status' } })).stdout, '');
});

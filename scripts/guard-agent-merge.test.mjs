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

test('denies a GraphQL merge whatever flags come before graphql', () => {
	denied(`${GH} api --paginate graphql -f query='mutation { mergePullRequest(input:{pullRequestId:"x"}) { clientMutationId } }'`);
	denied(`${GH} api -X POST graphql -f query='mutation { mergePullRequest(input:{}) { clientMutationId } }'`);
	denied(`gh api -H "Accept: application/json" graphql -f query='mutation { mergeBranch(input:{}) { clientMutationId } }'`);
});

test('denies GraphQL mutations that move or delete a branch ref', () => {
	denied(`${GH} api graphql -f query='mutation { updateRef(input:{refId:"x", oid:"y"}) { clientMutationId } }'`);
	denied(`${GH} api graphql -f query='mutation { deleteRef(input:{refId:"x"}) { clientMutationId } }'`);
	denied(`${GH} api graphql -f query='mutation { createCommitOnBranch(input:{}) { clientMutationId } }'`);
});

test('denies a GraphQL query the guard cannot read', () => {
	denied(`${GH} api graphql -f query="$(cat q.txt)"`);
	denied(`${GH} api graphql -f query=$QUERY`);
	denied(`${GH} api graphql -f query=\`cat q.txt\``);
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

test('denies REST writes to refs, file contents, branch protection and a PR base', () => {
	denied(`${GH} api -X PATCH repos/av3000/japanese-vma/git/refs/heads/develop -f sha=abc -F force=true`);
	denied(`${GH} api repos/av3000/japanese-vma/git/refs -f ref=refs/heads/develop -f sha=abc`);
	denied(`${GH} api -X PUT repos/av3000/japanese-vma/contents/README.md -f message=x -f content=eA== -f branch=develop`);
	denied(`${GH} api -X DELETE repos/av3000/japanese-vma/branches/develop/protection`);
	denied(`${GH} api -X PATCH repos/av3000/japanese-vma/pulls/668 -f base=develop`);
});

test('allows REST reads of refs and contents', () => {
	allowed(`${GH} api repos/av3000/japanese-vma/git/refs/heads/develop --jq .object.sha`);
	allowed(`${GH} api repos/av3000/japanese-vma/contents/README.md`);
	allowed(`${GH} api -X GET repos/av3000/japanese-vma/pulls/668`);
});

test('reads the PR number wherever the flags are', () => {
	denied(`${GH} pr merge --squash -R av3000/japanese-vma 651`);
	denied(`${GH} pr merge --repo=av3000/japanese-vma --squash 651`);
	allowed(`${GH} pr merge --squash --repo=av3000/japanese-vma 668 --subject "x"`);
});

test('denies a merge in another repository', () => {
	denied(`${GH} pr merge 668 -R someone/else --squash`);
	denied(`${GH} pr merge 668 --repo someone/else`);
	denied(`GH_REPO=someone/else ${GH} pr merge 668 --squash`);
});

test('checks a merge from the PowerShell tool', () => {
	denied(`& 'C:\\Program Files\\GitHub CLI\\gh.exe' pr merge 651 --squash`);
	allowed(`& 'C:\\Program Files\\GitHub CLI\\gh.exe' pr merge 668 --squash`);
});

test('allows text that only mentions pr merge', () => {
	allowed(`echo "| 12:00 | epic agent | pr merge blocked by the guard |" >> .agent-runs/epic-659.md`);
	allowed(`git commit -m "explain why gh pr merge needs a number"`);
	allowed(`${GH} pr comment 668 --body "merged with gh pr merge --squash"`);
});

test('denies a merge hidden in eval, a command substitution or a pipe into a shell', () => {
	denied(`eval "gh pr merge 651 --squash"`);
	denied(`echo "gh pr merge 651" | bash`);
	denied(`X="$(gh pr merge 651)"`);
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

test('the hook denies a real payload on stdin, from Bash or PowerShell', () => {
	for (const tool_name of ['Bash', 'PowerShell']) {
		const command = `${GH} api graphql -f query='mutation { mergePullRequest(input:{}) { clientMutationId } }'`;
		const result = run(JSON.stringify({ tool_name, tool_input: { command } }));
		assert.equal(result.status, 0);
		assert.match(result.stdout, /"permissionDecision":"deny"/);
	}
});

test('the hook stays silent on an unparseable payload without merge, and on an allowed command', () => {
	assert.equal(run('').stdout, '');
	assert.equal(run('not json').stdout, '');
	assert.equal(run(JSON.stringify({ tool_input: { command: 'git status' } })).stdout, '');
});

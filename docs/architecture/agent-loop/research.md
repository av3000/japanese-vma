# Research: issue-to-PR agent loops (October 2026)

What other teams and vendors do, collected on 2026-10-14 to back the design in [README.md](./README.md). Every claim names its source; the list is at the end. Claims marked **(unverified)** came from secondary sources or could not be checked against the original. Vendor products change monthly, so re-check section 2 before building.

## 1. The words, and where they come from

| Term | Plain meaning | Source and status |
| --- | --- | --- |
| **Agentic engineering** | Building software with coding agents as the main producers of code, with the engineer designing the system around them | Simon Willison's guide defines it. Karpathy's coinage around February 2026 is reported (unverified at source). |
| **Harness engineering** | Designing everything around the model (docs, tools, checks, sandboxes) so its output can be trusted | OpenAI's February 2026 post; Birgitta Böckeler's martinfowler.com article generalises it. |
| **Loop engineering** | Designing the system that prompts the agents (schedules, worktrees, skills, connectors, memory) instead of prompting by hand. One level above the harness. | Addy Osmani, June 2026. Real but new: not a settled term, much of the coverage is vendor marketing, and Osmani himself is sceptical about cost and verification. |
| **Backpressure** | Automatic checks that reject wrong output so the agent must fix it before moving on. Their speed matters. | Geoffrey Huntley's Ralph posts. |
| **Guides and sensors** | Guides steer before the agent acts (instructions, skills). Sensors check after (tests, linters, AI review). Sensors are either computational (fast, deterministic) or inferential (AI-judged). | Böckeler, harness engineering article. |
| **Context rot** | Recall gets worse as the context window fills, so context is a budget, not a bucket | Anthropic, context engineering post. |
| **Maker/checker** | The agent that wrote the code is not the one that judges it | Anthropic, harness design post (March 2026); Osmani. |
| **Lethal trifecta** | Private data + untrusted content + a way to send data out = an exploitable agent | Simon Willison, June 2025. |

"Golden tasks" is not an established term in anything read. The nearest ideas are regression eval suites (Anthropic) and the hidden "scenarios" StrongDM keeps outside the repo so agents cannot overfit to them.

## 2. Where the agent runs

| Product | Trigger | Runtime | Billing | Status |
| --- | --- | --- | --- | --- |
| **`anthropics/claude-code-action`** | `label_trigger` (default `claude`), `assignee_trigger`, `trigger_phrase` (default `@claude`), or a `prompt` on any event including `schedule` | Your Actions runner | Actions minutes + an API key, a subscription token from `claude setup-token`, or Bedrock/Vertex/Foundry | Generally available as v1 since August 2025 |
| **Claude Code cloud sessions** | claude.ai/code, the apps, `claude --cloud` | Anthropic VM (4 vCPU, 16 GB) with PHP 8.3, Composer, Node, Docker, PostgreSQL 16 and Redis preinstalled; network allow-list by default | Subscription | Available |
| **Claude Code routines** | Schedule, an API `/fire` call, GitHub pull request and release events (not issues) | Anthropic VM, acting as you | Subscription | Research preview |
| **Copilot cloud agent** (renamed from "coding agent") | Assign an issue to Copilot, `@copilot` on a PR, the agents panel, the API. No label trigger. | GitHub-managed environment configured by `copilot-setup-steps.yml` (only `steps`, `permissions`, `runs-on`, `services`, `snapshot`, `timeout-minutes` honoured; 59-minute cap) | AI credits + Actions minutes since June 2026 | Generally available since September 2025 |
| **Agent HQ** (Claude and Codex inside GitHub) | Assign an issue to Claude or Codex, `@claude` / `@codex` on PRs | Same as Copilot | Copilot credits, not your Claude or OpenAI subscription | Public preview since February 2026 |
| **GitHub Agentic Workflows (`gh-aw`)** | `label_command` (removes the label after firing), slash commands, schedules, events; `roles:` defaults to admin, maintainer, write | Your runner. Markdown compiled to `.lock.yml`. The agent job is read-only; writes are "safe outputs" done by separate jobs after checks. | Actions + an API key (Claude subscription tokens not supported) | Technical preview since February 2026, still 0.x |
| **OpenAI Codex** | `@codex review` and `@codex <task>` on PRs; no documented issue trigger. `openai/codex-action` runs on any event you wire. | OpenAI cloud (legacy environments being replaced) or your runner | ChatGPT plan, or an API key for the action. OpenAI's CI guide says not to use ChatGPT sign-in on public repos. | GitHub integration mid-migration |
| **Google Jules** | Label `jules` on an issue | Google VM; reads root `AGENTS.md` | Daily task quotas by tier | Available |
| **Cursor, OpenHands, Devin** | `@cursor`; label `openhands` or `@openhands`; `/devin` on PRs | Vendor clouds | Various | Only worth it if already in use |

Details that changed the design:

- **claude-code-action pushes a branch; it does not open the PR.** By default it posts a link to a pre-filled "create PR" page. An unattended run must be told to run `gh pr create` and allowed to.
- **Commits made with the workflow's `GITHUB_TOKEN` do not start other workflows.** Anthropic's troubleshooting says so for the action, and `gh-aw` documents the same for its PRs. CI would never run on such a PR, so the agent needs a GitHub App token.
- **The official Claude GitHub App asks for a lot:** Anthropic's own permission table lists Workflows, Actions and Repository hooks with write access. The same page documents a custom App with only Contents, Issues and Pull requests as the alternative.
- **`base_branch` defaults to the repository's default branch.** Here that is `master`, not `develop`.
- **Instructions:** the action reads `CLAUDE.md`. Claude Code reads `AGENTS.md` directly only from v2.1.277 and only when no `CLAUDE.md` exists; a `CLAUDE.md` containing `@AGENTS.md` works everywhere. Whether the action's bundled CLI is that new is unverified.
- **Plugins:** the action has `plugins` and `plugin_marketplaces` inputs, so plugin skills can be installed per job.
- **Subscription tokens in CI:** Anthropic announced, then paused, a separate allowance for automated use in mid-2026 **(unverified)**. Check the current terms before relying on a subscription token for unattended runs.
- **Scheduled workflows in public repos** are disabled after 60 days without repository activity.

## 3. Security

### What went wrong elsewhere

Every incident below has the same root: an agent read text an attacker wrote while it held secrets or a write token, and GitHub itself was the way out.

| When | What | Root cause |
| --- | --- | --- |
| May 2025 | **GitHub MCP "toxic agent flow"** (Invariant Labs): a malicious issue in a public repo made a user's agent read their private repos and publish the contents in a PR | An agent with private read access, public write access and untrusted input. Invariant called it architectural, not a bug. |
| Aug 2025 | **Nx "s1ngularity"**: a stolen npm token published malware that then used local AI CLIs with permission-skipping flags to hunt for secrets | The entry was classic script injection of a PR title into a `pull_request_target` workflow. A branch-modified workflow leaked the publish token. |
| Dec 2025 | **"PromptPwnd"** (Aikido): Gemini CLI, Claude Code, Codex and GitHub AI Inference actions put issue or PR text into prompts while holding tokens; a proof of concept made the agent write its keys into an issue | Untrusted text in the prompt, secrets in the environment |
| Feb 2026 | **"Clinejection"**: a triage workflow open to any user, with the issue title in the prompt, led to cache poisoning that reached the nightly publish job; a rogue release followed | `allowed_non_write_users: "*"` plus a shared Actions cache |
| Apr 2026 | **"Comment and Control"**: PR titles, issue comments and hidden HTML comments made Claude Code's security review, Gemini CLI's action and Copilot's agent leak secrets; Copilot's firewall allowed GitHub, so data left by `git push` | Untrusted text, and an allow-list that has to include GitHub |
| 2026 | **claude-code-action advisories**: before v1.0.74 it loaded a PR's `.mcp.json` (remote code execution, GHSA-8q5r-mmjf-575q); a chain fixed in v1.0.94 trusted any `[bot]` actor and leaked OIDC variables **(details from secondary reporting)**; a CodeAnt report shows that on `issues: labeled`, a Triage user could re-apply a label so their comment entered a maintainer-authorised run (fix status unverified as of August 2026) | Trusting the trigger, not the content |
| Jun 2026 | **Microsoft**: prompt injection made the action's Read tool open `/proc/self/environ`; Bash was scrubbed, Read was not. Fixed in Claude Code 2.1.128. | Every tool is an exfiltration path, not only the shell |
| May 2026 | **Gemini CLI "TrustIssues"** (Pillar Security): triage on `issues: opened`, `--yolo`, the checkout token left in `.git/config`; escalated through `workflow_dispatch` to a second token | Persisted credentials, an agent with no tool limits |

### GitHub facts that matter here

- **Who can label:** Read access cannot apply labels; Triage and above can. Issue templates apply their `labels:` to anyone's new issue, so never put a trigger label in a template.
- **`issues` and `issue_comment` events fire for anyone** on a public repo. The workflow, not GitHub, decides whether to act.
- **`pull_request_target` and `workflow_run`** run with the base repository's secrets and a write token. Never run PR code in them (GitHub Security Lab).
- **Secrets and branches:** the "no secrets for forks" rule only covers forks. A PR from a branch in the same repo can run a modified workflow that names any repository secret. That is what Nx's attacker used. Environment secrets are only released to jobs that name the environment, and the environment can require a reviewer and limit which branches deploy.
- **`GITHUB_TOKEN`:** setting any `permissions:` key sets every other scope to none. There is no `workflows` scope; pushes touching `.github/workflows` with this token are refused (community-confirmed, not in the docs).
- **Rulesets:** require PRs, status checks and code-owner review, block force pushes. The file-path push restriction is not available for public repositories, so a CI path check has to do that job.
- **Actions policy:** a repository setting can require every action to be pinned to a full commit SHA (since August 2025).
- **`step-security/harden-runner`:** free for public repos; audits, then blocks, outbound traffic per job. It cannot stop exfiltration through github.com.
- **`author_association`** describes who wrote the issue, not who added the label. Label triggers must check `github.event.sender`.

### Vendor guardrails, checked

- **claude-code-action:** issue and PR events need a triggering user with write access. Bots are refused unless listed in `allowed_bots`. `allowed_non_write_users` is documented as a significant risk with only best-effort secret scrubbing. Bash is off unless allowed. It never force-pushes. It has no network filter of its own.
- **Copilot cloud agent:** only write-access users can trigger it, and comments from others are never passed in. It pushes only to `copilot/` branches and opens draft PRs. The person who asked cannot approve its PR. Workflows on its PRs wait for a human to click "Approve and run". The firewall is on by default but covers only the agent's shell, not MCP servers or setup steps.
- **codex-action:** write access required by default; recommends a read-only Codex job and a separate job for writes, with `persist-credentials: false`.
- **gh-aw:** the agent container holds no secrets, outbound traffic goes through a firewall, and writes are checked after the agent exits.

### The resulting checklist

Must, before any unattended run:

1. Deploy secrets in a protected environment the agent job never names.
2. Explicit, minimal `permissions:`; the repository default set to read.
3. A minimal custom GitHub App as the agent's identity; not the official App, not `GITHUB_TOKEN`.
4. Trigger only when the label sender **and** the issue author are the owner. Never set `allowed_non_write_users` or `allowed_bots`.
5. No trigger label in templates; nobody else with Triage access.
6. Never interpolate `github.event.*` text into `run:` or the prompt; pass the number.
7. An explicit tool allow-list, no web tools, turn and time caps, one concurrency group.
8. A dedicated, spend-capped model key that you assume can leak.
9. The action pinned to a SHA at a release after the 2026 fixes.

Should, soon after: split the agent job (read-only) from the job that pushes; `harden-runner`; the SHA-pinning setting; a path guard for `.github/**` and agent instructions; no shared caches between untrusted and release jobs; leave `show_full_output` off.

## 4. How other teams build the loop

### OpenAI: harness engineering (February 2026)

A team of three, later seven, built an internal product of about a million lines with no hand-written code, at roughly 3.5 merged PRs per engineer per day. What they relied on:

- **The repo is the system of record.** Anything decided in chat or in someone's head is invisible to the agent, so plans, design notes and quality grades live in `docs/`.
- **`AGENTS.md` is a map, not a manual:** about 100 lines pointing into `docs/`. One big instruction file failed: it crowds out the task, rots, and can't be checked by a machine.
- **Mechanical enforcement:** a fixed layer model checked by custom linters and structural tests, whose error messages tell the agent how to fix the problem.
- **An agent-legible runtime:** the app boots per worktree with its own logs, metrics and traces the agent can query, and browser access for screenshots.
- **Garbage collection:** a weekly day of cleaning up "AI slop" did not scale. They wrote down "golden principles" and run scheduled agents that look for drift and open small refactor PRs.
- **Merge philosophy:** few blocking gates and fast reruns of flaky tests. The post says this would be irresponsible at low throughput. Their bottleneck was human QA time.

### OpenAI: Symphony (April 2026)

An open specification (Apache-2.0) with an experimental Elixir implementation. The issue tracker (Linear) is the control plane:

- **A poller** dispatches eligible issues every 30 seconds. "Eligible" means: in an active state, not already claimed, blockers resolved, and a free slot.
- **Caps:** `max_concurrent_agents` (default 10), `max_turns` (default 20), stall detection after 5 minutes, exponential backoff.
- **States:** Backlog → Todo → In Progress → Human Review → Merging (through a `land` skill, never a direct merge) → Done, with Rework looping back.
- **Policy in the repo:** a `WORKFLOW.md` holds the configuration and prompt template, reloaded live.
- **Each ticket** must have a validation or test-plan section, and each run keeps one "workpad" comment.
- **Follow-ups:** out-of-scope findings become linked issues.
- **A lesson they report:** a rigid state machine for the agent's own steps was too limiting; giving it an objective plus tools worked better.
- **Throughput:** they report a large rise in landed PRs. That figure is the company's own.

### Anthropic

- **Building effective agents** (December 2024): start with workflows (fixed code paths) before agents (the model chooses the path); useful patterns include gated chains, orchestrator-workers and evaluator-optimizer; agents need ground truth from the environment, checkpoints and stop conditions.
- **Effective harnesses for long-running agents:** an initializer run creates an `init.sh`, a progress file and a feature list where every item starts failing. Each later session reads the git log and progress file, picks the top unfinished item, works on exactly one, and may only flip its `passes` flag. Editing tests is forbidden.
- **Harness design for long-running apps** (March 2026): agents grade their own work too generously, so a separate evaluator with written criteria and browser access does the judging. Context resets with a structured hand-off beat ever-longer sessions. Harness assumptions go stale as models improve, so revisit them.
- **Claude Code best practices:** the single most useful thing is a check the agent can run itself. Ask for evidence, not assertions. Headless `claude -p` has `--max-turns`, `--max-budget-usd` and `--allowedTools`. Use worktrees for parallel sessions and a fresh session to review.
- **Cost:** agents use about 4 times the tokens of chat, and multi-agent systems about 15 times (multi-agent research post).
- **Evals:** distinguish capability suites (can it do new things) from regression suites (does it still do old ones); report pass@k (at least one of k tries passes) and pass^k (all k pass) separately.

### Ralph (Geoffrey Huntley, July 2025)

The simplest loop there is: a shell loop feeds the same prompt file to the agent, forever. One process, one task per iteration, the same specs and plan file loaded each time. The plan is disposable and regenerated when it drifts. The hard engineering is backpressure: fast types, tests and lints that reject wrong output. Anthropic ships it as the `ralph-wiggum` plugin (also `ralph-loop`): a Stop hook re-feeds the prompt until `--completion-promise` text appears or `--max-iterations` is reached. It suits tasks with automatic verification and is poor for judgement work.

### Spec-driven development and its critics

GitHub's Spec Kit (constitution → specify → plan → tasks → implement) and AWS Kiro (requirements → design → tasks) turn a request into Markdown specs before code. Böckeler tried them and reported:

- one workflow does not fit every size: a small bug became 16 acceptance criteria;
- reviewing pages of Markdown is harder than reviewing code;
- agents still ignore or over-apply instructions, so the specs give a false sense of control.

Her harness article adds that behaviour is the hardest thing to check: tests written by the same agent passing is not yet good enough evidence, and a sensor that never fires tells you nothing.

**What this means here:** the issue shape is already a light spec-first practice, with a cap of 3 to 7 criteria per slice. Böckeler's critique argues against adding a heavier spec layer on top. Put the effort into sensors instead: `verify.sh`, hooks and the fresh-context review.

## 5. Failure modes practitioners report

| Failure | Evidence | Counter |
| --- | --- | --- |
| **Review becomes the bottleneck** | Faros (vendor telemetry, correlational): teams with high AI adoption merged 98% more PRs while review time rose 91% and PR size 154% | WIP caps, small PRs, proof of work in the PR |
| **Gaming the checks** | METR documents frontier models editing tests or scoring code to pass | Forbid test edits, hidden scenarios, graders the agent can't change |
| **Flaky verification** | Anthropic's evals post: isolate each trial in a clean environment | One shared setup, reruns before blame |
| **Scope creep** | Common to Symphony, Ralph and Anthropic's harness posts | One item per run; follow-ups as issues |
| **Stale context** | OpenAI's doc-gardening; Anthropic's note that harness assumptions go stale | Scheduled drift sweeps; delete docs the code now explains |
| **Cost blowups** | Anthropic's 4× and 15× token figures | Turn, budget, iteration and concurrency caps; spend limits on keys (Willison) |
| **Prompt injection** | Section 3 | Section 3 |

## 6. Maturity ladders

Published ladders mostly describe the human's role:

- **Dan Shapiro's five levels** (January 2026): from autocomplete, through "intern" and "junior developer", to the developer as reviewer, to an engineering team of agents, to a "dark factory" where nobody reads the code. StrongDM is the usual level-5 example.
- **Steve Yegge's eight stages** in "Welcome to Gas Town": from almost no AI to writing your own orchestrator.

An infrastructure ladder, **synthesised for this research** (not from a single source):

| Level | What exists | This repo |
| --- | --- | --- |
| L0 | Interactive prompting | |
| L1 | Harnessed interactive: `AGENTS.md` map, skills, verify commands | **Here**, mostly |
| L2 | Spec-gated delegation: acceptance criteria as definition of ready, on-demand runs, PRs with evidence, turn and budget caps | Spec gate here; delegation missing |
| L3 | Self-verifying with an independent review: isolated runtime, fast backpressure, fresh-context reviewer, stop at human review | Phases 1–3 |
| L4 | Queue-driven dispatch: label or status poller, WIP cap, retries, telemetry, agent-filed follow-ups | Phases 2 and 5 |
| L5 | Self-maintaining factory: drift-sweeping agents, harness evals with hidden scenarios, risk-tiered auto-merge | Not a goal. Sources agree it only pays at very high throughput with strong verification. |

## 7. What the design takes from each

| From | Taken | Left out, and why |
| --- | --- | --- |
| Symphony | Tracker as control plane, eligibility rules, WIP cap, workpad comment, Human Review state, follow-ups as issues | Polling every 30 seconds (GitHub events are enough), 10 concurrent agents (one reviewer) |
| OpenAI harness | Repo as record, mechanical rules with fix hints, drift sweeps | Relaxed merge gates (low throughput here), per-worktree observability stack (not needed yet) |
| Anthropic harnesses | One item per run, a progress record, forbid test edits, separate evaluator | A JSON feature list (the epic's checklist does the same job) |
| Ralph | Backpressure first; hard iteration caps | The infinite loop itself: here, a human dispatches each run |
| Copilot agent | Draft PRs only, agent cannot approve or merge, write-access triggers | The product itself (D1) |
| gh-aw | Read-only agent job, writes checked afterwards | The product itself, until it leaves preview |
| Böckeler | Guides vs sensors; invest in sensors, not more spec | Spec Kit or Kiro-style spec layers |

## Sources

Read directly unless marked.

**Vendors and products**
- Claude Code GitHub Actions: https://code.claude.com/docs/en/github-actions
- claude-code-action repo, `action.yml`, `docs/security.md`: https://github.com/anthropics/claude-code-action
- Claude Code memory and `AGENTS.md`: https://code.claude.com/docs/en/memory#agents-md
- Claude Code on the web and cloud environments: https://code.claude.com/docs/en/claude-code-on-the-web, https://code.claude.com/docs/en/cloud-environments
- Claude Code routines: https://code.claude.com/docs/en/routines
- Claude Code best practices, CLI reference, headless: https://code.claude.com/docs/en/best-practices, https://code.claude.com/docs/en/cli-reference, https://code.claude.com/docs/en/headless
- Ralph plugin: https://github.com/anthropics/claude-code/blob/main/plugins/ralph-wiggum/README.md
- Copilot cloud agent risks and mitigations: https://docs.github.com/en/copilot/concepts/agents/cloud-agent/risks-and-mitigations
- Copilot coding agent GA: https://github.blog/changelog/2025-09-25-copilot-coding-agent-is-now-generally-available/
- Third-party agents (Agent HQ): https://docs.github.com/en/copilot/concepts/agents/about-third-party-agents
- GitHub Agentic Workflows: https://github.github.com/gh-aw/, https://github.com/github/gh-aw, https://github.blog/changelog/2026-02-13-github-agentic-workflows-are-now-in-technical-preview/
- gh-aw security architecture: https://github.blog/ai-and-ml/generative-ai/under-the-hood-security-architecture-of-github-agentic-workflows/
- OpenAI Codex GitHub integration, action, CI auth: https://learn.chatgpt.com/docs/third-party/github, https://github.com/openai/codex-action, https://learn.chatgpt.com/docs/auth/ci-cd-auth
- Jules: https://jules.google/docs/running-tasks/
- Cursor cloud agents: https://cursor.com/docs/cloud-agent
- OpenHands GitHub: https://docs.openhands.dev/openhands/usage/cloud/github-installation

**Engineering writing**
- OpenAI, Harness engineering: https://openai.com/index/harness-engineering/
- OpenAI, Symphony: https://openai.com/index/open-source-codex-orchestration-symphony/ and https://github.com/openai/symphony
- Anthropic, Building effective agents: https://www.anthropic.com/engineering/building-effective-agents
- Anthropic, Effective context engineering: https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents
- Anthropic, Harness design for long-running apps: https://www.anthropic.com/engineering/harness-design-long-running-apps
- Anthropic, Demystifying evals: https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents
- Anthropic, Multi-agent research system: https://www.anthropic.com/engineering/multi-agent-research-system
- Geoffrey Huntley, Ralph: https://ghuntley.com/ralph/ and https://ghuntley.com/loop/
- Birgitta Böckeler, SDD tools: https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html
- Birgitta Böckeler, Harness engineering: https://martinfowler.com/articles/exploring-gen-ai/harness-engineering.html
- Spec Kit: https://github.com/github/spec-kit; Kiro specs: https://kiro.dev/docs/specs/
- Addy Osmani, Loop engineering: https://addyosmani.com/blog/loop-engineering/
- Simon Willison, Agentic engineering patterns: https://simonwillison.net/guides/agentic-engineering-patterns/
- Simon Willison, Designing agentic loops: https://simonwillison.net/2025/Sep/30/designing-agentic-loops/
- Dan Shapiro, five levels: https://www.danshapiro.com/blog/2026/01/the-five-levels-from-spicy-autocomplete-to-the-software-factory/
- StrongDM's software factory (via Willison): https://simonwillison.net/2026/Feb/7/software-factory/
- Steve Yegge, Welcome to Gas Town: https://steve-yegge.medium.com/welcome-to-gas-town-4f25ee16dd04
- METR, reward hacking: https://metr.org/blog/2025-06-05-recent-reward-hacking/
- Faros AI productivity report (vendor): https://www.faros.ai/blog/ai-software-engineering

**Security**
- Simon Willison, The lethal trifecta: https://simonwillison.net/2025/Jun/16/the-lethal-trifecta/
- Invariant Labs, GitHub MCP: https://invariantlabs.ai/blog/mcp-github-vulnerability
- Aikido, PromptPwnd: https://www.aikido.dev/blog/promptpwnd-github-actions-ai-agents
- Comment and Control: https://oddguan.com/blog/comment-and-control-prompt-injection-credential-theft-claude-code-gemini-cli-github-copilot/
- Microsoft, Claude Code GitHub Action case: https://www.microsoft.com/en-us/security/blog/2026/06/05/securing-ci-cd-in-agentic-world-claude-code-github-action-case
- claude-code-action advisory GHSA-8q5r-mmjf-575q: https://github.com/anthropics/claude-code-action/security/advisories/GHSA-8q5r-mmjf-575q
- CodeAnt, post-authorisation injection: https://codeant.ai/security-research/claude-code-action-post-authorization-injection
- The Next Web on the v1.0.94 fixes (secondary): https://thenextweb.com/news/claude-code-github-action-prompt-injection-flaw
- Pillar Security, TrustIssues: https://pillar.security/blog/my-agentic-trust-issues-from-prompt-injection-to-supply-chain-compromise-on-gemini-cli
- Clinejection: https://adnanthekhan.com/posts/clinejection/
- Nx advisory and Wiz analysis: https://github.com/nrwl/nx/security/advisories/GHSA-cxm3-wv7p-598c, https://www.wiz.io/blog/s1ngularity-supply-chain-attack
- GitHub Security Lab, preventing pwn requests: https://securitylab.github.com/resources/github-actions-preventing-pwn-requests/
- GitHub, SHA pinning policy: https://github.blog/changelog/2025-08-15-github-actions-policy-now-supports-blocking-and-sha-pinning-actions/
- Cloud Security Alliance research note on AI in GitHub Actions: https://labs.cloudsecurityalliance.org/research/csa-research-note-ai-github-actions-security-20260503-csa-st/

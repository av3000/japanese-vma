# Review report template

`post-review-to-pr` parses this shape, so keep the headings and the finding line format exactly.

```markdown
## Review: <branch> → develop (#<issue>, #<issue>)

**Verdict:** Blocked | Changes requested | Ready with suggestions | Ready
**Scope:** <n> files · <areas, e.g. client/routes, processor-api/Articles> · size S | M | L
**Risk:** <one-way | two-way> door · blast radius <one word, e.g. none / page / feature / app / data> · touches <contract / migrations / auth / queues / none>

### Acceptance criteria

| AC | Status | Evidence |
| --- | --- | --- |
| #503/AC-1 | Met | `AGENTS.md:104` |
| #503/AC-2 | Partial | `docs/agents/issue-tracker.md:20`: shape present, but no follow-up rule |

### Findings

| ID | Type | Criticality | Where | One line |
| --- | --- | --- | --- | --- |
| F1 | Spec | Critical | `path/to/file.ts:42` | AC-2 asks for 403, code returns 404 |

**F1 [Spec · Critical]** #458/AC-2 "a private catalogue as another user: 403" — `processor-api/app/…/Controller.php:42`
What is wrong. Why it matters. Suggested fix (test-first if it is behaviour, refactor if it is structure).

### Follow-ups (not findings)

- <pre-existing problem or out-of-scope idea>: candidate `needs-triage` issue.

### Not checked

- <check that did not run, and why>
```

Rules:

- Finding IDs are `F1`, `F2`, … in table order: Critical first, then Warning, then Suggestion.
- Each finding line starts `**F<n> [<Type> · <Criticality>]**`, then the criterion or rule reference, then `—` and `path:line` when there is one.
- Omit empty sections except Acceptance criteria and Findings. Write "No findings." when there are none.

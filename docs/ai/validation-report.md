# Documentation Baseline Validation Report

> **Status:** Validated baseline
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Maintainers, reviewers, and AI-assisted contributors

## Verdict Summary

The baseline covers the approved structure: cross-project synthesis, four architecture views, four six-document feature packets, evidence traceability, two audience-focused recaps, and a tested validator.

The documents consistently distinguish verified current, inferred current, target, legacy/debt, and open-question claims. No application runtime, production provider, or external dataset was exercised during documentation assembly.

## Structural Coverage

| Area | Expected | Result |
|---|---:|---|
| Cross-project synthesis documents | 7 | Present after assembly |
| Architecture views | 4 | Present |
| Feature packets | 4 | Present |
| Documents per feature packet | 6 | Present |
| Feature documents total | 24 | Present |
| Documentation validator | 1 | Present |
| Validator behavior tests | 8 | Implemented |

## Traceability Assessment

The [evidence manifest](./evidence-manifest.md) groups sources by guidance, frontend, backend, contracts, runtime, tests, and migration plans. Stable architecture, requirement, and feature claim IDs link representative claims to repository paths.

Traceability is strongest for:

- article v1 list/detail/write/processing/PDF behavior;
- catalogue v1 list/detail/write/item/PDF behavior;
- Japanese material v1 list/detail contracts;
- article/catalogue comment reads and generic comment creation;
- generated-contract workflow;
- configured deployment topology.

Traceability is intentionally weaker and labelled open for:

- live provider state;
- administrative article route completeness;
- end-to-end queue/realtime health;
- reply inclusion behavior;
- remaining legacy post and sentence-comment authorization details.

## Consistency Assessment

### Current and target separation

- Registered v1 routes with incomplete controller evidence are not presented as completed capabilities.
- Legacy community and saved-list behavior is documented as current compatibility/debt, not as a preferred pattern.
- Migration plans are treated as target evidence until code/tests prove completion.
- Configured Render/GCP/Upstash topology is not described as live-verified.

### Terminology

- **Catalogue** is the preferred product/architecture term; **list** remains only for legacy identifiers and the current object-template label.
- Article publicity and article moderation status are distinct.
- Known/learned catalogue state and general saved membership are distinct.
- Comment read identity and generic write identity remain visibly different.

### Cross-document alignment

The product requirements, current-target comparison, architecture views, and feature packets agree on the leading migration order: contract/schema first, caller migration second, legacy retirement last.

## Conflict Assessment

Four conflicts are retained rather than hidden:

1. Root README Laravel version versus dependency/guidance version.
2. Registered routes versus implementation/test completeness.
3. Generated schema versus intended runtime payload.
4. Repository deployment configuration versus live provider state.

Each conflict has a resolution check in the evidence manifest.

## Validator Test Evidence

The validator was developed test-first. Before `docs/scripts/validate-docs.ps1` existed, the harness failed with exit code 1 and reported the missing validator. After implementation, the harness reported:

```text
PASS: complete baseline passes
PASS: missing cross-project file fails
PASS: incomplete feature packet fails
PASS: broken relative Markdown link fails
PASS: absolute local path fails
PASS: unfinished-work marker fails
PASS: missing metadata fails
PASS: missing repository path fails
Tests: 8 passed, 0 failed
```

## Assembly Validation Evidence

The first real-baseline run occurred before the three final recap/report files existed. It correctly failed with four errors: the three missing required files and the resulting broken dashboard link. No metadata, repository-path, or other link errors were reported in the files already assembled.

## Final Verification Evidence

A fresh full verification run after assembly reported:

```text
Tests: 8 passed, 0 failed
Documentation validation passed for 35 Markdown file(s).
Whitespace check passed for 40 task files.
```

`git diff --check` exited successfully without whitespace errors. `git status --short` showed the task-owned documentation and scripts as untracked and retained the pre-existing unrelated modified/untracked paths. No task file was staged or committed.

## Commands

```powershell
pwsh -NoProfile -File docs/scripts/validate-docs.tests.ps1
pwsh -NoProfile -File docs/scripts/validate-docs.ps1
git diff --check
git status --short
```

## Review Limitations

- No frontend build, typecheck, or application test suite was required for documentation-only changes.
- No backend container, database, or DB-backed feature test was run.
- No browser flow was exercised.
- No GitHub Actions, GitLab, Render, GCP, Upstash, Sentry, or Reverb provider state was inspected.
- Existing unrelated modified and untracked files were preserved and are not validated by this report.

## Open Questions

The open verification register in [Evidence Manifest](./evidence-manifest.md) remains the authoritative list. Open items are actionable verification needs, not missing baseline sections.

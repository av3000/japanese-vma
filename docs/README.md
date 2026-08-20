# Japanese VMA Documentation

This directory is the shared knowledge base for Japanese VMA. It is written for maintainers, reviewers, and AI-assisted contributors without depending on a specific AI product.

## Start Here

Read these sources in order before changing the repository:

1. [`AGENTS.md`](../AGENTS.md) for repository-wide rules.
2. [`CONTEXT.md`](../CONTEXT.md) for accepted domain language and architecture direction.
3. [`client/AGENTS.md`](../client/AGENTS.md) or [`processor-api/AGENTS.md`](../processor-api/AGENTS.md) for application-specific constraints.
4. [Technical handoff](./ai/recaps/technical-handoff.md) for the shortest implementation-oriented overview.
5. The owning architecture view or feature packet for the task.

## Knowledge Ownership

| Knowledge | Owner |
|---|---|
| Mandatory contributor and agent rules | Root and scoped `AGENTS.md` files |
| Accepted domain language and architecture direction | `CONTEXT.md` |
| Cross-project synthesis and traceability | [`docs/ai/`](./ai/) |
| System, application, deployment, and integration views | [`docs/architecture/`](./architecture/) |
| User-visible behavior and feature migration | [`docs/feature-artifacts/`](./feature-artifacts/) |
| Focused migration work | [`docs/legacy-v1-migration/`](./legacy-v1-migration/) |
| Issue tracker and triage conventions | [`docs/agents/`](./agents/) |

The owning document should contain the detailed explanation. Other documents should link to it rather than maintain a second copy.

## Evidence Labels

Baseline documents use five labels:

| Label | Meaning |
|---|---|
| **Verified current** | Supported directly by code, configuration, tests, or repository documentation. |
| **Inferred current** | Supported by several sources but not exercised during the review. |
| **Target** | Required by accepted guidance, context, or migration plans. |
| **Legacy/debt** | Present, but not a pattern to extend. |
| **Open question** | Requires a decision, conflicting-source resolution, or runtime verification. |

The [evidence manifest](./ai/evidence-manifest.md) maps important claims to their repository sources.

## Cross-Project Baseline

- [Architecture description](./ai/architecture-description.md)
- [Product requirements](./ai/product-requirements.md)
- [Current-to-target state](./ai/current-target-state.md)
- [Evidence manifest](./ai/evidence-manifest.md)
- [Validation report](./ai/validation-report.md)
- [Project dashboard](./ai/recaps/project-dashboard.md)
- [Technical handoff](./ai/recaps/technical-handoff.md)

## Architecture Views

- [System context](./architecture/system-context.md)
- [Application boundaries](./architecture/application-boundaries.md)
- [Deployment and runtime](./architecture/deployment-and-runtime.md)
- [Data and integrations](./architecture/data-and-integrations.md)

## Feature Packets

Each packet uses the same six views: abstract, vocabulary, behavior, mutations, user stories, and current-to-target migration.

- [Articles](./feature-artifacts/articles/abstract.md)
- [Catalogues and saved lists](./feature-artifacts/catalogues-and-saved-lists/abstract.md)
- [Japanese study material](./feature-artifacts/japanese-study-material/abstract.md)
- [Community and engagement](./feature-artifacts/community-and-engagement/abstract.md)

## Existing Focused Documents

- [Legacy-to-v1 backend/frontend issue backlog](./legacy-v1-migration/backend-frontend-issue-backlog.md)
- [GitHub issue creation template](./legacy-v1-migration/github-issue-creation-template.md)
- [Issue tracker guidance](./agents/issue-tracker.md)
- [Triage labels](./agents/triage-labels.md)
- [Domain-document guidance](./agents/domain.md)

Other focused documentation may coexist under `docs/`. Add it to this map when it becomes a durable source rather than a temporary working note.

## Maintenance Workflow

When behavior or architecture changes:

1. Update the owning feature or architecture document.
2. Update the evidence manifest when a major claim or source changes.
3. Keep current and target states visibly separate.
4. Record conflicts or unverified runtime assumptions in the validation report.
5. Run the documentation checks.

```powershell
pwsh -NoProfile -File docs/scripts/validate-docs.tests.ps1
pwsh -NoProfile -File docs/scripts/validate-docs.ps1
git diff --check
```

The validator is read-only. It checks baseline structure, metadata, relative links, repository-path references, local absolute paths, and unfinished-work markers.

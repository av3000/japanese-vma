# Triage Labels

The skills speak in terms of five canonical triage roles and two categories. This file maps them to this repo's GitHub labels.

## State (exactly one per issue)

| Skill role | Tracker label | Meaning |
| --- | --- | --- |
| `needs-triage` | `needs-triage` | Maintainer needs to evaluate this issue |
| `needs-info` | `needs-info` | Waiting on reporter for more information |
| `ready-for-agent` | `ready-for-agent` | Fully specified and ready for an AFK agent |
| `ready-for-human` | `ready-for-human` | Requires human implementation |
| `wontfix` | `wontfix` | Will not be actioned |

`ready-for-agent` requires an `## Acceptance criteria` checklist and at least one "Out" line under Scope. Without both, use `needs-info` or `needs-triage`.

## Category (exactly one per issue)

| Category | Tracker label | Meaning |
| --- | --- | --- |
| `bug` | `bug` | Existing behaviour is wrong |
| `enhancement` | `enhancement` | New or changed behaviour |

Area labels (`frontend`, `backend`, `ci`, `documentation`, `epic`, …) are added on top, as many as apply.

Review follow-ups start as `needs-triage`.

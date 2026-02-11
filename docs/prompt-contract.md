# Prompt Contract Templates

Use these templates to make prompts consistent, explicit, and high-signal.

## 1) Universal Prompt Contract (Base)

Use this for any request type, then extend with frontend/backend templates as needed.

```md
## Task Type
- [ ] planning-only
- [ ] implementation
- [ ] bugfix
- [ ] review/refactor

## Objective
- Desired outcome:
- Why now:

## Scope (in)
- Files/folders/modules allowed to change:

## Non-goals (out)
- What must NOT change:

## Constraints
- Architecture/layering constraints:
- Libraries/patterns to prefer:
- Libraries/patterns to avoid:
- Performance/security/compatibility constraints:

## Inputs and Outputs
- Input contract:
- Output contract:
- Error/edge-case expectations:

## Reuse Expectations
- Existing components/services/modules to reuse:

## Validation Requirements
- Commands/checks required:
- UI screenshot required? (yes/no)

## Required Response Format
1) Discovery summary
2) Plan
3) File-by-file changes
4) Risks and tradeoffs
5) Checks run and results

## Definition of Done
- Functional acceptance criteria:
- Architecture acceptance criteria:
- Testing acceptance criteria:
```

---

## 2) Frontend Template (`client/`)

Use for React/UI changes.

```md
Use `react-best-practices` skill.
Follow `AGENTS.md` and `client/AGENTS.md`.

## Objective
- Build/refactor: <component/feature>
- User value: <why>

## Scope (in)
- Allowed files/folders: <paths>

## Non-goals (out)
- Explicit exclusions: <paths/behaviors>

## Frontend Constraints
- React Query for server-state patterns.
- Zustand for app/global client-state patterns.
- Zod for validation where practical.
- Reuse existing shared components/services before adding new primitives.
- Keep changes incremental (no broad rewrite unless requested).

## Functional Requirements
- <bullet list of required behavior>

## API/Props Contract
- Props or payload shape:
- Controlled vs uncontrolled behavior (if relevant):
- Accessibility expectations:

## Edge Cases
- Empty/loading/error states:
- Duplicate input handling:
- Keyboard interactions:

## Validation
- Run lint/typecheck/tests relevant to touched files.
- Provide screenshot if visible UI changes were made.

## Required Response Format
1) Discovery summary
2) Plan
3) File-by-file changes
4) Risks/tradeoffs + anti-patterns avoided
5) Commands run and outcomes
```

---

## 3) Backend Template (`processor-api/`)

Use for endpoint and domain/service/repository changes.

```md
Follow `AGENTS.md` and `processor-api/AGENTS.md`.

## Objective
- Endpoint/use-case: <name>
- Desired business outcome:

## Scope (in)
- Allowed files/folders: <paths>

## Non-goals (out)
- Explicit exclusions:

## Backend Constraints
- Preserve layered flow: Route -> Controller -> Request -> DTO/VO -> Service -> Repository -> Resource.
- Keep transport concerns out of domain entities.
- Reuse existing response/error patterns in touched module.
- Prefer v1 route/module patterns for new or migrated work.

## Request/Response Contract
- Request fields:
- Validation rules:
- Response schema/status codes:
- Error cases:

## Authorization + Business Rules
- Ownership/role checks:
- Domain invariants:

## Data/Persistence Changes
- Repository methods to add/change:
- Migration implications (if any):

## Validation
- Run targeted PHPUnit/feature tests for affected area.
- Add/update tests for success/failure/edge cases.

## Required Response Format
1) Discovery summary
2) Plan
3) File-by-file changes
4) Risks/tradeoffs
5) Commands run and outcomes
```

---

## 4) Endpoint Migration Template (Legacy `api.php` -> `api_v1.php`)

Use for migration planning and implementation of legacy endpoints into v1 architecture.

```md
Follow `AGENTS.md` and `processor-api/AGENTS.md`.

## Migration Target
- Legacy endpoint:
- New v1 endpoint:
- Entity/use-case:

## Scope (in)
- Files/folders allowed to change:

## Non-goals (out)
- What remains untouched in this migration:

## Discovery Requirements
- Identify legacy route/controller/service flow.
- Identify nearest v1 pattern to mirror.
- Identify side effects (hashtags, engagement, jobs/events, etc.).

## Migration Constraints
- Keep migration incremental and behavior-safe.
- Document intentional behavior changes explicitly.
- Keep contract compatibility notes clear.

## Required Plan Sections
1) Current state summary
2) Phase-by-phase implementation plan
3) Layer-by-layer mapping (Request, DTO/VO, Service, Repository, Resource)
4) Must-do now vs follow-up improvements
5) Definition of done
6) Test plan

## Validation
- Run targeted tests for migrated behavior.
- Include failure and edge-case coverage.
```

---

## 5) Three Example Prompts

### A) Frontend example — `InputTags`

```md
Use `react-best-practices` skill.
Follow `AGENTS.md` and `client/AGENTS.md`.

Build a reusable `InputTags` shared UI component that creates chips on Enter/comma and supports chip removal.

Scope:
- `client/src/components/shared/...` only

Non-goals:
- No backend/API changes.
- No broad design-system rewrite.

Constraints:
- Reuse existing shared Chip component.
- Handle dedupe, empty values, backspace remove-last behavior.
- Keep component typed and accessible.

Validation:
- Run lint/typecheck/tests for touched files.
- Include screenshot artifact.

Output format:
1) discovery summary
2) plan
3) file-by-file changes
4) risks + anti-patterns avoided
5) commands run
```

### B) Backend example — single endpoint update

```md
Follow `AGENTS.md` and `processor-api/AGENTS.md`.

Refactor article update endpoint into v1 style with Request, DTO/VO, Service, and Repository layers.

Scope:
- Article v1 route/controller/request/dto/service/repository/resource files.

Non-goals:
- No unrelated endpoint refactors.

Constraints:
- Keep layered boundaries strict.
- Handle optional update payload and empty-update request explicitly.
- Reuse existing v1 response/error style.

Validation:
- Run targeted PHPUnit feature tests for success/failure/edge cases.

Output format:
1) discovery summary
2) plan
3) file-by-file changes
4) risks/tradeoffs
5) commands run
```

### C) Planning-only example — migration roadmap

```md
Planning-only task. Do not change code.
Follow `AGENTS.md` and `processor-api/AGENTS.md`.

Create phased migration plan for catalogue CRUD endpoints from legacy routes to v1.

Required sections:
1) current state
2) phase plan
3) dependency/risk matrix
4) must-do now vs follow-up
5) acceptance criteria
6) test strategy
```

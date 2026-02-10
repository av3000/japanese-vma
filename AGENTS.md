# AGENTS.md

This file provides repository-wide guidance for AI agents and contributors working in `japanese-vma`.

## 1) Repository Overview
- **Goal:** Japanese learning platform with article/list/community features and Japanese language resources.
- **Main apps:**
  - `processor-api/` → Laravel API (legacy + v1 routes coexist).
  - `client/` → React frontend (Vite + Storybook + Vitest toolchain).
  - `docs/` → project demo assets.
- **Preferred direction:** Continue migrating feature work toward backend v1 architecture and strongly typed frontend patterns.

## 2) High-Level Architecture
- **Backend style:** Domain-driven modular monolith with layered responsibilities.
- **Backend flow to preserve:**
  - Route → Controller → Request validation → DTO/Value Objects → Service/Application layer → Repository/Model → Resource/HTTP response.
- **Frontend style:** Route-driven UI with shared components, centralized axios client, and mixed state strategies (Zustand + React Query).
- **Cross-cutting concerns:**
  - Authentication/authorization, validation, pagination, and consistent error responses.

## 3) Backend Conventions (`processor-api`)
- **Route policy:**
  - Keep existing legacy endpoints stable unless task explicitly requires legacy refactor.
  - Add new/refactored endpoints in `routes/api_v1.php` whenever possible.
- **Layering policy:**
  - Keep transport concerns (request objects, HTTP status shape) out of domain entities.
  - Prefer application services and repository interfaces over direct controller-to-model logic.
  - Use DTOs for operation contracts, especially for create/update flows.
- **Validation policy:**
  - Prefer dedicated Request classes per endpoint.
  - For PATCH/partial updates, validate optional fields and explicitly handle "no update fields provided".
- **Identifier policy:**
  - Follow existing v1 conventions around UUID/entity identifiers per endpoint.
- **Response policy:**
  - Reuse existing response/resource patterns in the module being modified.
  - Keep success/error envelope consistent with neighboring v1 endpoints.
- **Authorization policy:**
  - Enforce ownership/role checks in appropriate layer (policy/service), not ad-hoc in many places.

## 4) Frontend Conventions (`client`)
- **Data access:**
  - Use shared axios service and existing API modules.
  - Keep authentication token handling centralized.
- **UI structure:**
  - Prefer existing shared components before introducing new one-off UI primitives.
  - Keep feature routes lazy-loaded and aligned with existing routing patterns.
- **State strategy:**
  - Use React Query for server-state patterns.
  - Use Zustand for app/global client-state patterns (prefer over Redux for new work).
- **Validation strategy:**
  - Use Zod schemas for frontend request/response and form data validation where practical.
- **Type safety:**
  - Prefer typed interfaces/types inferred from or aligned with Zod schemas for API payloads and route-level data mapping.

## 5) Migration Playbook (Legacy API → v1)
- **Discovery first:**
  - Locate legacy route/controller/service path.
  - Locate nearest v1 equivalent and align naming/shape.
  - Identify side effects (hashtags, likes, comments, last-operations, etc.).
- **Plan implementation in layers:**
  - Route definition.
  - Request validation contract.
  - DTO + value object mapping.
  - Service orchestration and domain rules.
  - Repository/data persistence method(s).
  - Resource/response mapping.
- **Compatibility notes:**
  - Preserve behavior intentionally or document intentional changes.
  - Keep migration incremental and testable.
- **Done criteria for migrated endpoint:**
  - Endpoint exists in v1 with documented request/response behavior.
  - Validation/authorization/error handling are explicit.
  - Tests added/updated for success + failure + edge cases.

## 6) Testing & Verification Expectations
- **Backend checks (when backend changes):**
  - Run targeted PHPUnit tests for modified area.
  - Add/adjust feature tests for endpoint behavior.
- **Frontend checks (when frontend changes):**
  - Run lint/typecheck/tests relevant to touched files.
  - For visible UI changes, capture a screenshot artifact when tooling/environment allows.
- **General:**
  - Prefer targeted checks first, then broader suite when practical.
  - If environment constraints block a check, state it clearly in the final report.

## 7) Change Management & Safety
- **Keep diffs focused:**
  - Avoid unrelated refactors in the same change.
  - Prefer small, reviewable commits with clear intent.
- **Document behavior-impacting changes:**
  - Call out API contract differences.
  - Mention migration/deployment implications if applicable.
- **Do not silently change patterns:**
  - If deviating from local conventions, explain why in PR notes.

## 8) Prompting Guidelines for Future Agents
- **When asking for plans:**
  - Include scope, non-goals, required output format, and acceptance criteria.
  - Ask for discovery summary before implementation steps.
- **When asking for code changes:**
  - Specify touched modules, endpoint contract, auth rules, and tests to add/update.
- **When asking for architecture feedback:**
  - Request split between "must-do now" and "follow-up improvements".

## 9) Definition of a High-Quality Agent Output
- **Clarity:**
  - Explicit assumptions, constraints, and tradeoffs.
- **Traceability:**
  - File-by-file change summaries and validation commands.
- **Reliability:**
  - Meaningful tests/checks, with failures or limitations reported honestly.
- **Pragmatism:**
  - Incremental, maintainable changes over speculative over-engineering.

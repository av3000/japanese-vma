# AGENTS.md (client)

This file defines **frontend-specific** guidance for changes under `client/`.

## 1) Frontend Architecture Direction
- **App style:** Route-driven React app with shared components and feature modules.
- **Data fetching/server state:**
  - Prefer React Query patterns for server state.
  - Keep request logic in existing API/service modules.
- **Client/global state:**
  - Prefer Zustand for app/global client state.
  - Do not introduce new Redux-based patterns for new work.
- **Validation and contracts:**
  - Prefer Zod schemas for request/response and form validation where practical.
  - Keep TypeScript types aligned with or inferred from Zod schemas.

## 2) Frontend Implementation Conventions
- **Routing/UI composition:**
  - Keep feature routes aligned with existing route patterns.
  - Reuse existing shared components before introducing one-off primitives.
- **API integration:**
  - Use centralized axios/service conventions already present.
  - Keep auth token behavior centralized (avoid duplicating auth mechanics).
- **Styling:**
  - Follow existing styling approach in touched area.
  - Avoid introducing parallel style systems for isolated changes.

## 3) Quality & Validation Expectations
- **For frontend code changes:**
  - Run lint, typecheck, and relevant tests for touched surface area.
- **For visible UI changes:**
  - Capture a screenshot artifact when environment/tooling permits.
- **When checks cannot run:**
  - Report limitation clearly and provide best-effort local verification.

## 4) Refactor & Migration Guardrails
- **Refactors should be incremental:**
  - Avoid large rewrites unless explicitly requested.
  - Keep behavior stable unless a behavior change is requested.
- **Contract changes:**
  - If API contracts or UI behavior changes, document before/after impacts.
- **Code organization:**
  - Keep concerns separated (presentation vs state vs data access).

## 5) Preferred Output Characteristics
- **Explain tradeoffs:** note why chosen pattern fits existing codebase.
- **Be explicit on risk:** list user-visible and integration risks.
- **Be test-oriented:** tie implementation claims to executed checks.

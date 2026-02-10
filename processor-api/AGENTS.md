# AGENTS.md (processor-api)

This file defines **backend-specific** guidance for changes under `processor-api/`.

## 1) Backend Architecture Direction
- **Style:** Domain-driven modular monolith with layered responsibilities.
- **Preferred request flow:**
  - Route → Controller → Request validation → DTO/Value Objects → Service/Application layer → Repository/Model → Resource/HTTP response.
- **Primary direction:**
  - Keep legacy behavior stable where needed.
  - Prefer new/refactored work in v1 architecture/routes when practical.

## 2) Route & Endpoint Policy
- **Legacy coexistence:**
  - Do not break legacy endpoints unless explicitly requested.
- **v1 preference:**
  - For new/refactored endpoints, prefer `routes/api_v1.php` conventions.
- **Migration behavior:**
  - Preserve existing behavior intentionally, or document intentional changes.

## 3) Layering & Domain Boundaries
- **Controller responsibilities:**
  - Coordinate request handling and mapping only.
  - Avoid embedding heavy business logic.
- **Service responsibilities:**
  - Orchestrate business rules and use-cases.
  - Enforce ownership/authorization rules at appropriate layer.
- **Repository responsibilities:**
  - Encapsulate persistence/data access concerns.
- **Boundary rule:**
  - Keep HTTP/transport details from leaking into domain entities.

## 4) Validation, DTO, and Identifier Conventions
- **Validation:**
  - Prefer dedicated Request classes per endpoint.
  - For partial updates, validate optional fields and handle empty-update payloads explicitly.
- **DTOs/Value Objects:**
  - Use DTO contracts for operation inputs/outputs where meaningful.
  - Use value objects where they improve invariants and type safety.
- **Identifiers:**
  - Follow existing v1 UUID/entity identifier conventions in the touched module.

## 5) Response & Error Handling
- **Response consistency:**
  - Reuse existing module resource/response patterns.
  - Keep success/error envelope aligned with neighboring endpoints.
- **Error behavior:**
  - Prefer explicit, typed/structured error handling patterns.
  - Do not hide contract-significant failures.

## 6) Testing & Verification Expectations
- **When backend code changes:**
  - Run targeted PHPUnit tests for affected feature/module.
  - Add or update feature tests for endpoint success/failure/edge cases.
- **Verification reporting:**
  - List exact commands run and outcomes.
  - Clearly report environment constraints when checks are blocked.

## 7) Refactor Guardrails
- **Incremental over broad rewrites:**
  - Keep changes focused and reviewable.
- **No silent contract drift:**
  - Call out payload/response/auth changes explicitly.
- **Migration playbook:**
  - Discovery first (legacy path, v1 counterpart, side effects), then layered implementation.

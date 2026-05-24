# Testing Record

Skill creation followed documentation TDD as far as available tooling allowed.

## Pressure Scenarios

Scenario A: A controller validates a request, fetches multiple repositories, enriches counts/hashtags, maps a response, and records side effects.

Expected skill behavior: move orchestration into a use case/application service; keep controller as HTTP adapter; return a shaped DTO/resource.

Scenario B: A PDF/export service renders PDFs and also records downloads, checks authorization, and fetches unrelated metadata.

Expected skill behavior: separate feature export preparation, policy checks, PDF renderer adapter, download recording action, and HTTP response factory when responsibilities diverge; avoid shared-service accretion.

Scenario C: A repository returns ORM models directly to domain code, and a mapper contains business decisions.

Expected skill behavior: identify persistence leakage and mapper overreach; return domain/application shapes; move business policy to domain/application service/policy.

Scenario D: A simple CRUD endpoint is being over-architected with CQRS/events/interfaces everywhere.

Expected skill behavior: keep the smallest nearby repo pattern; reject CQRS/events/interfaces unless a current pressure justifies them.

## Baseline Without Skill

Subagent baseline was a partial red, not a total failure.

What it did well:

- Recommended moving controller orchestration into application services.
- Identified PDF service responsibility accretion.
- Identified ORM leakage and mapper policy overreach.
- Rejected default CQRS/event sourcing for simple CRUD.
- Said it would read actual files and preserve repo conventions.

Observed gaps to address:

- Did not provide actual repo file/class flow evidence because the pressure prompt did not include files.
- Suggested "Controller -> authorization -> Article/CataloguePdfExportService" for PDF, while this repo commonly keeps policy checks in application services/actions.
- Used generic patterns first; the new skill must force a concrete flow map before abstract commentary.

## Forward Test Expectations

The skill should make a future agent:

- read actual repo files before architecture claims
- provide concrete filenames/classes before abstract commentary
- avoid default CQRS/event sourcing
- keep business rules out of controllers/resources/mappers
- distinguish acceptable transitional code from real debt
- preserve repo conventions over generic folder templates

## Forward Test Status

Forward testing was run after drafting with a subagent instructed to use the new skill.

Result:

- Produced a repo-local v1 flow map before scenario advice.
- Produced a PDF export flow using feature export service, `PdfRendererInterface`, infrastructure renderer, `RecordDownloadAction`, and `PdfResponseFactory`.
- Moved scenario A orchestration into application services/actions.
- Identified scenario B as debt when reasons to change diverge, while preserving a transitional caveat.
- Identified scenario C as persistence leakage and mapper policy overreach.
- Rejected CQRS/events/interfaces by default for scenario D.

Forward-test gaps found:

- The service/action split threshold needed more explicit guidance.
- Raw ORM transitional acceptance still requires reading the local interface/tests.
- Legacy endpoint contract preservation still requires inspecting touched legacy controllers and tests.

Refactor applied:

- Clarified that feature services may keep authorization, side effects, and persistence calls when they are one use case and match nearby style, but should split reused, external, differently failing, or unrelated-change responsibilities into focused actions/adapters.

# Backend Response And Errors

Each rule below is a citable rule in the `backend-standards` skill (`.claude/skills/backend-standards/rules/<ID>.md`), with an Incorrect/Correct example.

For v1 endpoints:

- Success responses are a named Resource or an `App\Shared\Http\TypedResults` helper, never ad hoc JSON: `RESULT-typed-results`.
- Service-level failures are `App\Shared\Results\Result` with typed error classes: `RESULT-result-typed-errors`.
- Pagination, envelope shape and include flags follow the nearest v1 module: `RESULT-envelope-matches-module`.
- A Request class rejects invalid input before the service runs: `CONTRACT-request-per-endpoint`.

When changing contracts:

- Contract changes are explicit, and legacy behaviour stays unless a migration moves it to v1.
- One endpoint family never mixes legacy JSON and `TypedResults`: `RESULT-no-mixed-envelopes`.

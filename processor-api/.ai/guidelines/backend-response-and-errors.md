# Backend Response And Errors

For v1 endpoints:

- Return success responses through `App\Shared\Http\TypedResults`.
- Return service-level failures through `App\Shared\Results\Result` and typed error classes.
- Keep pagination, envelope shape, and include-flag behavior aligned with the nearest existing v1 module.
- Use request validation classes to reject invalid inputs before service orchestration.

When changing contracts:

- Make the change explicit in the task summary or implementation notes.
- Keep legacy behavior unless a migration intentionally moves to a new v1 contract.
- Do not mix legacy JSON patterns and v1 `TypedResults` patterns in the same endpoint family without a clear reason.

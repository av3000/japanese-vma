# Backend Architecture Boundaries Index

This is the canonical index for the skill. Use it before opening the detailed rule files.

## Read Order

1. `../references/project-domain-map.md` for observed repo names and uncertainty notes.
2. `layer-boundaries.md` for dependency direction and layer placement.
3. `controllers-use-cases-services.md` when controller/service/action responsibility is in question.
4. `repositories-mappers-ports.md` when persistence, mapping, DTO, or adapter shape is in question.
5. `review-checklist.md` before finalizing an architecture review or refactor plan.

## Minimum Evidence Standard

Before making architecture claims, cite concrete local files/classes in the flow. At minimum:

- route file or endpoint family
- controller and request
- application service/action/policy
- repository interface and implementation, if persistence is involved
- mapper/builder, if shape translation is involved
- domain DTO/model/value object/resource involved in the response

If a layer is missing, say it is missing and explain whether the absence is acceptable for the local complexity.

## Decision Shortcut

| Smell | First place to inspect |
| --- | --- |
| Controller has multiple repository/service calls, enrichment, side effects | `controllers-use-cases-services.md` |
| Service renders, authorizes, records, fetches unrelated metadata | `controllers-use-cases-services.md` |
| Domain code sees ORM/persistence models | `repositories-mappers-ports.md` |
| Mapper decides visibility, authorization, or lifecycle rules | `repositories-mappers-ports.md` |
| Simple endpoint gains CQRS/events/interfaces by default | `layer-boundaries.md` |

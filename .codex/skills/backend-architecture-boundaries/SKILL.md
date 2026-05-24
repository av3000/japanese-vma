---
name: backend-architecture-boundaries
description: Use when reviewing, refactoring, or designing backend architecture in japanese-vma around thin controllers, use cases, services, repositories, mappers, ports/adapters, clean architecture, hexagonal architecture, dependency leaks, DTO/resource seams, and layer-boundary questions.
---

# Backend Architecture Boundaries

## Overview

Use this skill to keep backend changes aligned with this repo's modular monolith direction while applying clean architecture and ports/adapters judgment. Start from actual files and the current flow before recommending abstractions.

## First Move

1. Read root `AGENTS.md`, `processor-api/AGENTS.md`, and the touched module's controller, request, service/action, repository interface, infrastructure repository/mapper, domain DTO/model/value object, resource, and tests.
2. Give a concrete flow map with filenames/classes before architecture commentary.
3. Apply the repo flow unless a local file proves a different convention:

`routes/api_v1.php -> Http/v1 Controller -> Request -> DTO or Value Object -> Application Service/Action/Policy -> Repository Interface -> Infrastructure Repository/Model/Mapper -> Resource -> TypedResults`

## Quick Reference

| Question | Load |
| --- | --- |
| Where should code live? | `rules/layer-boundaries.md` |
| Controller, use case, or service? | `rules/controllers-use-cases-services.md` |
| Repository, mapper, port, or adapter? | `rules/repositories-mappers-ports.md` |
| Reviewing a proposed change? | `rules/review-checklist.md` |
| Need terms and definitions? | `references/concepts.md` |
| Need repo domain language? | `references/project-domain-map.md` |

## Default Rules

- Dependencies point inward: infrastructure may depend on application/domain; application may depend on domain and ports; domain must not depend on HTTP, framework, database, API resources, or persistence models.
- Controllers translate HTTP in/out and delegate orchestration. They should not enrich lists, coordinate multiple repositories, record side effects, or hide business rules.
- Prefer feature-local services/actions/use cases over generic shared coordinators when responsibilities differ.
- Repositories hide persistence details. Returning raw ORM/persistence models upward is debt unless the nearby architecture explicitly accepts it.
- Mappers translate shape only. Business policy belongs in domain/application services, actions, or policies.
- Resources/presenters shape outbound API contracts. Do not move business rules into resources to make a response easier.
- Keep simple CRUD simple. Do not recommend CQRS, events, interfaces, or aggregate machinery without a concrete current pressure.

## Common Mistakes

| Mistake | Correction |
| --- | --- |
| Starting from a generic clean-architecture folder template | Start from current repo namespaces and adjacent module patterns. |
| Calling every service a domain service | In this repo most orchestration lives in `app/Application`; reserve domain service for pure domain logic with no I/O. |
| Moving authorization to controllers by default | Check the local pattern first; policies are commonly used from application services/actions. |
| Treating transitional legacy code as automatically wrong | Mark legacy/v1 coexistence and preserve behavior unless migration is explicit. |
| Letting PDF/rendering services collect download tracking, authorization, metadata, and response details | Separate feature export preparation, rendering adapter, download recording action, and HTTP response factory when responsibilities diverge. |

## Index

Use `rules/index.md` as the navigation point. Do not duplicate rules into new files; extend the single relevant rule file instead.

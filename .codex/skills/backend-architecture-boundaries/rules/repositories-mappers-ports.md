# Repositories, Mappers, Ports, And Adapters

## Repository Port Rule

A repository interface is a port used by application code. In this repo those ports usually live under `app/Application/**/Interfaces/Repositories`.

Repository ports should expose use-case-friendly operations, not table-shaped CRUD by default. Return domain/application-level shapes such as domain models, collections, DTOs, paginators intentionally mapped to domain items, or narrow read DTOs.

## Repository Adapter Rule

Infrastructure repositories implement ports and own persistence concerns:

- ORM/query builder usage
- eager loading and batch loading
- database-specific filtering and sorting
- mapping persistence rows into domain/application shapes

Returning raw ORM models or arrays above infrastructure is a boundary leak unless a nearby interface documents it as accepted transitional debt.

## Mapper / Assembler Rule

Mappers translate shape. They may normalize persistence representation into domain-compatible values, but they must not become hidden policy containers.

Allowed mapper work:

- persistence model -> domain model
- domain model -> persistence fields
- raw storage values -> value-object constructor inputs
- relation-loaded children -> domain child objects

Not mapper work:

- authorization or ownership decisions
- visibility rules
- lifecycle transitions
- response-envelope decisions
- side effects

## DTO / Resource Seams

DTOs carry operation input/output shapes across application/domain seams. Resources/presenters carry API response shape at the HTTP edge.

Do not force a domain model to mimic the API payload. Add an edge resource or shaped DTO when the outward contract differs from the domain object.

## Ports And Composition

Ports belong inward from their adapters. Adapter implementations belong in infrastructure. Bind interfaces explicitly in providers such as repository or feature providers.

Use a port when the application needs to depend on a capability without knowing the external mechanism. Do not add a port just because a class exists.
